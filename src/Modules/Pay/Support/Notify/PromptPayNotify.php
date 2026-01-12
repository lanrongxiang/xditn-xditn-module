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
use Modules\Pay\Support\NotifyData\PromptPayNotifyData;
use Modules\VideoSubscription\Models\UserWallet;

/**
 * PromptPay 支付回调处理.
 */
class PromptPayNotify extends Notify
{
    /**
     * 退款回调处理.
     */
    public function refundNotify(): mixed
    {
        /** @var PromptPayNotifyData $notifyData */
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
            Log::channel('payment')->warning('PromptPay 退款回调订单不存在', [
                'platform' => 'promptpay',
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
            'promptpay',
            ['type' => 'refund', 'channel' => $notifyData->getChannel()]
        ));

        return true;
    }

    /**
     * 支付回调处理.
     */
    public function payNotify(): mixed
    {
        /** @var PromptPayNotifyData $notifyData */
        $notifyData = $this->data;

        if (!$notifyData->isPaySuccess()) {
            return false;
        }

        $tradeNo = $notifyData->getTradeNo();
        $outTradeNo = $notifyData->getOutTradeNo();

        if (empty($tradeNo)) {
            Log::channel('payment')->warning('PromptPay 回调订单号为空', [
                'platform' => 'promptpay',
                'out_trade_no' => $outTradeNo,
            ]);

            return false;
        }

        $order = $this->findOrder($tradeNo, $outTradeNo, true);
        if (!$order) {
            Log::channel('payment')->warning('PromptPay 回调订单不存在', [
                'platform' => 'promptpay',
                'order_no' => $tradeNo,
                'out_trade_no' => $outTradeNo,
            ]);

            return false;
        }

        if ($order->isPaySuccess()) {
            return true;
        }

        try {
            $this->updateOrderStatus($order, $tradeNo, $outTradeNo, $notifyData);
            $this->addUserCoins($order);
            $this->dispatchPaymentCompletedEvent($order, $tradeNo, $outTradeNo, $notifyData);
        } catch (\Throwable $e) {
            Log::channel('payment')->error('PromptPay 处理支付回调失败', [
                'platform' => 'promptpay',
                'order_id' => $order->id,
                'order_no' => $tradeNo,
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
     * 更新订单状态
     */
    protected function updateOrderStatus(Order $order, string $tradeNo, ?string $outTradeNo, PromptPayNotifyData $notifyData): void
    {
        $gatewayData = array_merge($order->gateway_data ?? [], [
            'order_no' => $tradeNo,
            'out_trade_no' => $outTradeNo,
            'channel' => $notifyData->getChannel(),
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
     * 触发支付完成事件.
     */
    protected function dispatchPaymentCompletedEvent(Order $order, string $tradeNo, ?string $outTradeNo, PromptPayNotifyData $notifyData): void
    {
        Event::dispatch(new PaymentCompleted(
            $order->id,
            $outTradeNo ?? $order->out_trade_no,
            'promptpay',
            [
                'order_type' => $order->rechargeOrder ? 'recharge' : 'normal',
                'user_id' => $order->user_id,
                'amount' => $order->amount,
                'channel' => $notifyData->getChannel(),
            ]
        ));
    }
}
