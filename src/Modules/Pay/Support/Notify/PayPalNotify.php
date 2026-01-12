<?php

declare(strict_types=1);

namespace Modules\Pay\Support\Notify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Enums\RefundStatus;
use Modules\Pay\Events\PaymentCompleted;
use Modules\Pay\Models\Order;
use Modules\Pay\Models\Transaction;
use Modules\Pay\Support\NotifyData\PayPalNotifyData;
use Modules\VideoSubscription\Models\UserWallet;

/**
 * PayPal 支付回调处理.
 */
class PayPalNotify extends Notify
{
    /**
     * 退款回调处理.
     */
    public function refundNotify(): mixed
    {
        /** @var PayPalNotifyData $notifyData */
        $notifyData = $this->data;

        if (!$notifyData->isRefundSuccess()) {
            return false;
        }

        $tradeNo = $notifyData->getTradeNo();
        $outTradeNo = $notifyData->getOutTradeNo();

        if (empty($tradeNo)) {
            return false;
        }

        $order = $this->findOrder($tradeNo, $outTradeNo);
        if (!$order) {
            Log::channel('payment')->warning('PayPal 退款回调订单不存在', [
                'platform' => 'paypal',
                'order_no' => $tradeNo,
                'out_trade_no' => $outTradeNo,
            ]);

            return false;
        }

        $order->refund_status = RefundStatus::SUCCESS->value;
        if ($outTradeNo) {
            $order->out_trade_no = $outTradeNo;
        }
        $order->save();

        Event::dispatch(new PaymentCompleted(
            $order->id,
            $outTradeNo ?? $order->out_trade_no,
            'paypal',
            ['type' => 'refund']
        ));

        return true;
    }

    /**
     * 支付回调处理.
     */
    public function payNotify(): mixed
    {
        /** @var PayPalNotifyData $notifyData */
        $notifyData = $this->data;

        $eventType = $notifyData->getEventType();
        $isPaySuccess = $notifyData->isPaySuccess();
        $tradeNo = $notifyData->getTradeNo();
        $outTradeNo = $notifyData->getOutTradeNo();
        $resource = $notifyData->getResource();

        // 如果是 CHECKOUT.ORDER.APPROVED 事件，需要自动捕获订单
        if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
            $orderId = $resource['id'] ?? $outTradeNo;
            if (!empty($orderId)) {
                try {
                    $this->captureOrder($orderId);

                    // 捕获成功后会收到 PAYMENT.CAPTURE.COMPLETED 事件，这里先返回
                    return true;
                } catch (\Throwable $e) {
                    Log::channel('payment')->error('PayPal 订单捕获失败', [
                        'platform' => 'paypal',
                        'order_id' => $orderId,
                        'error' => $e->getMessage(),
                    ]);
                    // 捕获失败，但不抛出异常，等待后续的 PAYMENT.CAPTURE.COMPLETED 事件
                }
            }
        }

        if (!$isPaySuccess) {
            return false;
        }

        if (empty($tradeNo)) {
            Log::channel('payment')->warning('PayPal 回调订单号为空', [
                'platform' => 'paypal',
                'event_type' => $eventType,
                'out_trade_no' => $outTradeNo,
            ]);

            return false;
        }

        // 查找订单或交易记录
        $order = $this->findOrder($tradeNo, $outTradeNo, true);
        $transaction = $this->findTransaction($tradeNo, $outTradeNo);

        if (!$order && !$transaction) {
            Log::channel('payment')->warning('PayPal 回调订单或交易不存在', [
                'platform' => 'paypal',
                'event_type' => $eventType,
                'order_no' => $tradeNo,
                'out_trade_no' => $outTradeNo,
            ]);

            return false;
        }

        // 处理订单支付
        if ($order) {
            if ($order->isPaySuccess()) {
                return true;
            }

            try {
                $this->updateOrderStatus($order, $tradeNo, $outTradeNo, $notifyData);
                $this->addUserCoins($order);
                $this->dispatchPaymentCompletedEvent($order, $tradeNo, $outTradeNo);
            } catch (\Throwable $e) {
                Log::channel('payment')->error('PayPal 处理支付回调失败', [
                    'platform' => 'paypal',
                    'order_id' => $order->id,
                    'order_no' => $tradeNo,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        // 处理交易记录（订阅支付）
        if ($transaction) {
            try {
                $this->updateTransactionStatus($transaction, $tradeNo, $outTradeNo, $notifyData);
                $this->dispatchPaymentCompletedEventForTransaction($transaction, $tradeNo, $outTradeNo);
            } catch (\Throwable $e) {
                Log::channel('payment')->error('PayPal 处理交易回调失败', [
                    'platform' => 'paypal',
                    'transaction_id' => $transaction->id,
                    'transaction_no' => $tradeNo,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        return true;
    }

    /**
     * 查找订单.
     */
    protected function findOrder(string $tradeNo, ?string $outTradeNo, bool $withRechargeOrder = false): ?Order
    {
        $query = Order::where('id', $tradeNo)
            ->orWhere('out_trade_no', $tradeNo)
            ->orWhere('out_trade_no', $outTradeNo);

        if ($withRechargeOrder) {
            $query->with('rechargeOrder');
        }

        return $query->first();
    }

    /**
     * 查找交易记录.
     */
    protected function findTransaction(string $tradeNo, ?string $outTradeNo): ?Transaction
    {
        return Transaction::where('transaction_no', $tradeNo)
            ->orWhere('transaction_no', $outTradeNo)
            ->first();
    }

    /**
     * 更新订单状态
     */
    protected function updateOrderStatus(Order $order, string $tradeNo, ?string $outTradeNo, PayPalNotifyData $notifyData): void
    {
        $gatewayData = array_merge($order->gateway_data ?? [], [
            'order_no' => $tradeNo,
            'out_trade_no' => $outTradeNo,
            'event_type' => $notifyData->getEventType(),
        ]);

        DB::table('pay_orders')
            ->where('id', $order->id)
            ->update([
                'pay_status' => PayStatus::SUCCESS->value,
                'paid_at' => \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s'),
                'out_trade_no' => $outTradeNo ?? $order->out_trade_no,
                'gateway_data' => json_encode($gatewayData),
            ]);

        $order->refresh();
    }

    /**
     * 增加用户金币
     */
    protected function addUserCoins(Order $order): void
    {
        if (!$order->rechargeOrder) {
            return;
        }

        $rechargeOrder = $order->rechargeOrder;
        $totalCoins = ($rechargeOrder->coins ?? 0) + ($rechargeOrder->bonus_coins ?? 0);

        if ($totalCoins <= 0) {
            return;
        }

        $wallet = UserWallet::getOrCreate($order->user_id);
        $wallet->addCoins($totalCoins, 'recharge', 'recharge_order', (string) $order->id, __('wallet.recharge'));
    }

    /**
     * 更新交易状态
     */
    protected function updateTransactionStatus(Transaction $transaction, string $tradeNo, ?string $outTradeNo, PayPalNotifyData $notifyData): void
    {
        $extraData = array_merge($transaction->extra_data ?? [], [
            'gateway_transaction_id' => $outTradeNo,
            'event_type' => $notifyData->getEventType(),
            'resource' => $notifyData->getResource(),
        ]);

        $transaction->update([
            'extra_data' => $extraData,
        ]);
    }

    /**
     * 触发支付完成事件（交易记录）.
     */
    protected function dispatchPaymentCompletedEventForTransaction(Transaction $transaction, string $tradeNo, ?string $outTradeNo): void
    {
        Event::dispatch(new PaymentCompleted(
            $transaction->transaction_no,
            $outTradeNo ?? $transaction->transaction_no,
            'paypal',
            [
                'order_type' => 'subscription',
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'related_type' => $transaction->related_type,
                'related_id' => $transaction->related_id,
            ]
        ));
    }

    /**
     * 触发支付完成事件.
     */
    protected function dispatchPaymentCompletedEvent(Order $order, string $tradeNo, ?string $outTradeNo): void
    {
        Event::dispatch(new PaymentCompleted(
            $order->id,
            $outTradeNo ?? $order->out_trade_no,
            'paypal',
            [
                'order_type' => $order->rechargeOrder ? 'recharge' : 'normal',
                'user_id' => $order->user_id,
                'amount' => $order->amount,
            ]
        ));
    }

    /**
     * 捕获 PayPal 订单.
     *
     * 当收到 CHECKOUT.ORDER.APPROVED 事件时，需要调用此方法捕获订单
     */
    protected function captureOrder(string $orderId): void
    {
        try {
            $paypal = app(\Modules\Pay\Support\PayPal::class);
            $ordersController = $paypal->getOrdersController();

            // 调用 PayPal API 捕获订单
            $apiResponse = $ordersController->captureOrder([
                'id' => $orderId,
            ]);

            $apiResponse->getResult();
        } catch (\Throwable $e) {
            Log::channel('payment')->error('PayPal 订单捕获异常', [
                'platform' => 'paypal',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
