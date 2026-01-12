<?php

declare(strict_types=1);

namespace Modules\Pay\Support\Notify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Enums\RefundStatus;
use Modules\Pay\Events\PaymentCompleted;
use Modules\Pay\Models\Order;
use Modules\Pay\Models\Transaction;
use Modules\Pay\Support\NotifyData\HaiPayNotifyData;
use Modules\VideoSubscription\Models\UserWallet;

/**
 * HaiPay 支付回调处理.
 *
 * 目前根据 HaiPay 公共文档中的「代收异步通知」结构处理支付成功场景：
 * https://doc.haipay.net/api/version2/CommonApi/
 */
class HaiPayNotify extends Notify
{
    /**
     * 退款回调（目前 HaiPay 公共文档主要描述代收，不单独处理退款）.
     */
    public function refundNotify(): mixed
    {
        return false;
    }

    /**
     * 支付回调处理.
     */
    public function payNotify(): mixed
    {
        /** @var HaiPayNotifyData $notifyData */
        $notifyData = $this->data;

        $tradeNo = $notifyData->getTradeNo();
        $outTradeNo = $notifyData->getOutTradeNo();
        $status = $notifyData->getStatus();

        if (!$notifyData->isPaySuccess()) {
            Log::channel('payment')->warning('HaiPay 回调状态非成功', [
                'platform' => 'haipay',
                'status' => $status,
                'trade_no' => $tradeNo,
                'out_trade_no' => $outTradeNo,
                'error_msg' => $notifyData->getErrorMessage(),
            ]);

            return false;
        }

        if (!$tradeNo) {
            Log::channel('payment')->warning('HaiPay 回调缺少商户订单号', [
                'platform' => 'haipay',
                'out_trade_no' => $outTradeNo,
            ]);

            return false;
        }

        // 使用事务保证订单与钱包更新一致
        return DB::transaction(function () use ($notifyData, $tradeNo, $outTradeNo) {
            /** @var Order|null $order */
            $order = Order::where('id', $tradeNo)
                ->orWhere('out_trade_no', $tradeNo)
                ->orWhere('out_trade_no', $outTradeNo)
                ->lockForUpdate()
                ->with('rechargeOrder')
                ->first();

            /** @var Transaction|null $transaction */
            $transaction = Transaction::where('transaction_no', $tradeNo)
                ->orWhere('transaction_no', $outTradeNo)
                ->lockForUpdate()
                ->first();

            if (!$order && !$transaction) {
                Log::channel('payment')->warning('HaiPay 回调订单或交易不存在', [
                    'platform' => 'haipay',
                    'trade_no' => $tradeNo,
                    'out_trade_no' => $outTradeNo,
                ]);

                return false;
            }

            // 处理订单支付（金币充值等）
            if ($order) {
                if (!$order->isPaySuccess()) {
                    $this->handleOrderPaid($order, $notifyData, $tradeNo, $outTradeNo);
                }
            }

            // 处理交易记录（订阅等）
            if ($transaction) {
                $this->handleTransactionPaid($transaction, $notifyData, $tradeNo, $outTradeNo);
            }

            return true;
        });
    }

    /**
     * 处理订单支付成功（如充值订单）.
     */
    protected function handleOrderPaid(Order $order, HaiPayNotifyData $notifyData, string $tradeNo, ?string $outTradeNo): void
    {
        $gatewayData = array_merge($order->gateway_data ?? [], [
            'order_no' => $tradeNo,
            'out_trade_no' => $outTradeNo,
            'haipay' => $notifyData->getRaw(),
        ]);

        DB::table('pay_orders')
            ->where('id', $order->id)
            ->update([
                'pay_status' => PayStatus::SUCCESS->value,
                'refund_status' => RefundStatus::NOT->value,
                'paid_at' => now()->format('Y-m-d H:i:s'),
                'out_trade_no' => $outTradeNo ?? $order->out_trade_no,
                'gateway_data' => json_encode($gatewayData, JSON_UNESCAPED_UNICODE),
            ]);

        $order->refresh();

        // 如果是充值订单，增加金币
        if ($order->rechargeOrder) {
            $rechargeOrder = $order->rechargeOrder;
            $totalCoins = ($rechargeOrder->coins ?? 0) + ($rechargeOrder->bonus_coins ?? 0);

            if ($totalCoins > 0) {
                $wallet = UserWallet::getOrCreate($order->user_id);
                $wallet->addCoins($totalCoins, 'recharge', 'recharge_order', (string) $order->id, __('wallet.recharge'));
            }
        }

        event(new PaymentCompleted(
            $order->id,
            $outTradeNo ?? $order->out_trade_no,
            'haipay',
            [
                'order_type' => $order->rechargeOrder ? 'recharge' : 'normal',
                'user_id' => $order->user_id,
                'amount' => $order->amount,
            ]
        ));
    }

    /**
     * 处理交易记录支付成功（如订阅订单）.
     */
    protected function handleTransactionPaid(Transaction $transaction, HaiPayNotifyData $notifyData, string $tradeNo, ?string $outTradeNo): void
    {
        $extraData = array_merge($transaction->extra_data ?? [], [
            'gateway_transaction_id' => $outTradeNo ?: $transaction->transaction_no,
            'haipay' => $notifyData->getRaw(),
        ]);

        $transaction->update([
            'extra_data' => $extraData,
        ]);

        event(new PaymentCompleted(
            $transaction->transaction_no,
            $outTradeNo ?? $transaction->transaction_no,
            'haipay',
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
