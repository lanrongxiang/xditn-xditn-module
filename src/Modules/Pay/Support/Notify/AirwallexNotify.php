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
use Modules\Pay\Support\NotifyData\AirwallexNotifyData;
use Modules\VideoSubscription\Models\UserWallet;

/**
 * Airwallex 支付回调处理.
 */
class AirwallexNotify extends Notify
{
    /**
     * 退款回调处理.
     */
    public function refundNotify(): mixed
    {
        /** @var AirwallexNotifyData $notifyData */
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
            Log::channel('payment')->warning('Airwallex 退款回调订单不存在', [
                'platform' => 'airwallex',
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
            'airwallex',
            ['type' => 'refund', 'event_type' => $notifyData->getEventType()]
        ));

        return true;
    }

    /**
     * 支付回调处理.
     */
    public function payNotify(): mixed
    {
        /** @var AirwallexNotifyData $notifyData */
        $notifyData = $this->data;

        if (!$notifyData->isPaySuccess()) {
            return false;
        }

        $tradeNo = $notifyData->getTradeNo();
        $outTradeNo = $notifyData->getOutTradeNo();
        $eventName = $notifyData->getEventType();

        // 对于 payment_attempt.settled 事件，如果没有 tradeNo，尝试通过 payment_intent_id 查找
        if (empty($tradeNo) && $eventName === 'payment_attempt.settled') {
            $paymentIntentId = $notifyData->getPaymentIntentId();
            if (!empty($paymentIntentId)) {
                // 通过 payment_intent_id 查找交易记录（extra_data 中可能存储了 payment_intent_id）
                $transaction = $this->findTransactionByPaymentIntentId($paymentIntentId);
                if ($transaction) {
                    $tradeNo = $transaction->transaction_no;
                }
            }
        }

        if (empty($tradeNo)) {
            Log::channel('payment')->warning('Airwallex 回调订单号为空', [
                'platform' => 'airwallex',
                'out_trade_no' => $outTradeNo,
                'event_type' => $eventName,
                'payment_intent_id' => $notifyData->getPaymentIntentId(),
            ]);

            return false;
        }

        // 查找订单或交易记录
        $order = $this->findOrder($tradeNo, $outTradeNo, true);
        $transaction = $transaction ?? $this->findTransaction($tradeNo, $outTradeNo);

        if (!$order && !$transaction) {
            Log::channel('payment')->warning('Airwallex 回调订单或交易不存在', [
                'platform' => 'airwallex',
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
                Log::channel('payment')->error('Airwallex 处理支付回调失败', [
                    'platform' => 'airwallex',
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
                Log::channel('payment')->error('Airwallex 处理交易回调失败', [
                    'platform' => 'airwallex',
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
     * 通过 payment_intent_id 查找交易记录.
     *
     * 查找 extra_data 中包含 payment_intent_id 的交易记录
     */
    protected function findTransactionByPaymentIntentId(string $paymentIntentId): ?Transaction
    {
        // 查找 extra_data JSON 字段中包含 payment_intent_id 的交易记录
        return Transaction::whereJsonContains('extra_data->payment_intent_id', $paymentIntentId)
            ->orWhereJsonContains('extra_data->gateway_transaction_id', $paymentIntentId)
            ->first();
    }

    /**
     * 更新订单状态
     */
    protected function updateOrderStatus(Order $order, string $tradeNo, ?string $outTradeNo, AirwallexNotifyData $notifyData): void
    {
        $gatewayData = array_merge($order->gateway_data ?? [], [
            'order_no' => $tradeNo,
            'out_trade_no' => $outTradeNo,
            'event_type' => $notifyData->getEventType(),
            'resource' => $notifyData->getResource(),
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
     * 更新交易状态
     */
    protected function updateTransactionStatus(Transaction $transaction, string $tradeNo, ?string $outTradeNo, AirwallexNotifyData $notifyData): void
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
     * 触发支付完成事件（订单）.
     */
    protected function dispatchPaymentCompletedEvent(Order $order, string $tradeNo, ?string $outTradeNo): void
    {
        Event::dispatch(new PaymentCompleted(
            $order->id,
            $outTradeNo ?? $order->out_trade_no,
            'airwallex',
            [
                'order_type' => $order->rechargeOrder ? 'recharge' : 'normal',
                'user_id' => $order->user_id,
                'amount' => $order->amount,
            ]
        ));
    }

    /**
     * 触发支付完成事件（交易记录）.
     */
    protected function dispatchPaymentCompletedEventForTransaction(Transaction $transaction, string $tradeNo, ?string $outTradeNo): void
    {
        Event::dispatch(new PaymentCompleted(
            // 这里需要传递交易单号（transaction_no），以便 SubscriptionPaymentListener 能根据 transaction_no 关联订阅
            $transaction->transaction_no,
            $outTradeNo ?? $transaction->transaction_no,
            'airwallex',
            [
                'order_type' => 'subscription',
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'related_type' => $transaction->related_type,
                'related_id' => $transaction->related_id,
            ]
        ));
    }
}
