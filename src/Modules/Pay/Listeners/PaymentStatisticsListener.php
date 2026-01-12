<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Pay\Events\PaymentCreated;
use Modules\Pay\Models\Order;

/**
 * 支付统计监听器.
 */
class PaymentStatisticsListener
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
            if (!$order || !$order->rechargeOrder) {
                return;
            }

            // 使用 pay_revenue_settlements 表更新充值统计
            $today = \Illuminate\Support\Carbon::now()->format('Y-m-d');
            $now = \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s');
            DB::table('pay_revenue_settlements')->updateOrInsert(
                ['settlement_date' => $today],
                [
                    'recharge_count' => DB::raw('COALESCE(recharge_count, 0) + 1'),
                    'recharge_amount' => DB::raw('COALESCE(recharge_amount, 0) + '.$order->getRawOriginal('amount')),
                    'updated_at' => $now,
                ]
            );
        } catch (\Throwable $e) {
            // 静默失败，不影响主流程
        }
    }
}
