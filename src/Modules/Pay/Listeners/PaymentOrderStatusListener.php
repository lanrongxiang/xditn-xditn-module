<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Events\PaymentCreated;
use Modules\Pay\Models\Order;

/**
 * 支付订单状态监听器.
 */
class PaymentOrderStatusListener
{
    /**
     * 处理支付订单创建事件.
     */
    public function handle(PaymentCreated $event): void
    {
        try {
            $orderId = $event->request['order_no'] ?? $event->request['order_id'] ?? null;
            if (!$orderId) {
                return;
            }

            $order = Order::find($orderId);
            if (!$order) {
                return;
            }

            // 更新订单状态为待支付
            if ($order->pay_status !== PayStatus::PENDING) {
                $order->pay_status = PayStatus::PENDING;
            }

            // 更新第三方订单号
            $outTradeNo = $event->response['out_trade_no'] ?? null;
            if ($outTradeNo && !$order->out_trade_no) {
                $order->out_trade_no = $outTradeNo;
            }

            // 更新网关数据
            if (!empty($event->response)) {
                $order->gateway_data = array_merge($order->gateway_data ?? [], $event->response);
            }

            $order->save();
        } catch (\Throwable $e) {
            // 静默失败，不影响主流程
        }
    }
}
