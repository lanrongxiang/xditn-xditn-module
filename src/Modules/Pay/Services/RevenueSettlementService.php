<?php

declare(strict_types=1);

namespace Modules\Pay\Services;

use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Enums\RefundStatus;
use Modules\Pay\Models\Order;
use Modules\Pay\Models\OrderRefund;
use Modules\Pay\Models\RevenueSettlement;

/**
 * 收入结算服务
 *
 * 从 pay_orders 表统计生成每日收入结算数据
 */
class RevenueSettlementService
{
    /**
     * 生成日收益结算.
     *
     * @param string $date 日期 Y-m-d，默认为昨天
     */
    public function generateDailySettlement(string $date): RevenueSettlement
    {
        $startDateTime = $date.' 00:00:00';
        $endDateTime = $date.' 23:59:59';

        // 查询当天的成功订单（按 paid_at 时间统计）
        $orders = Order::where('pay_status', PayStatus::SUCCESS)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDateTime, $endDateTime])
            ->with(['rechargeOrder', 'subscriptionOrder', 'purchaseOrder'])
            ->get();

        // 初始化统计变量
        $rechargeCount = 0;
        $rechargeAmount = 0;
        $subscriptionCount = 0;
        $subscriptionRevenue = 0;
        $purchaseCount = 0;
        $purchaseRevenue = 0;
        $renewalCount = 0;
        $renewalRevenue = 0;
        $refundCount = 0;
        $refundAmount = 0;

        // 按支付网关分类统计
        $gatewayBreakdown = [];

        foreach ($orders as $order) {
            // 注意：Order 模型的 amount 字段有 Attribute getter，会将分转换为元
            $amount = $order->getRawOriginal('amount'); // 获取原始值（分）

            // 按订单类型统计
            if ($order->rechargeOrder) {
                $rechargeCount++;
                $rechargeAmount += $amount;
            } elseif ($order->subscriptionOrder) {
                $subscriptionOrder = $order->subscriptionOrder;
                if ($subscriptionOrder->order_type === 1) {
                    // 首次订阅
                    $subscriptionCount++;
                    $subscriptionRevenue += $amount;
                } else {
                    // 续费
                    $renewalCount++;
                    $renewalRevenue += $amount;
                }
            } elseif ($order->purchaseOrder) {
                $purchaseCount++;
                $purchaseRevenue += $amount;
            }

            // 按支付平台统计
            // gateway_breakdown 存储的是元（用于显示），所以需要除以 100
            $platformName = $this->getPlatformName($order->platform);
            if (!isset($gatewayBreakdown[$platformName])) {
                $gatewayBreakdown[$platformName] = ['count' => 0, 'revenue' => 0];
            }
            $gatewayBreakdown[$platformName]['count']++;
            $gatewayBreakdown[$platformName]['revenue'] += round($amount / 100, 2); // 转换为元，保留2位小数
        }

        // 查询退款订单（按退款时间统计）
        $refundOrders = OrderRefund::where('refund_status', RefundStatus::SUCCESS)
            ->whereNotNull('refunded_at')
            ->whereBetween('refunded_at', [$startDateTime, $endDateTime])
            ->get();

        foreach ($refundOrders as $refund) {
            $refundCount++;
            $refundAmount += $refund->refund_amount;
        }

        // 计算汇总
        $totalRevenue = $rechargeAmount + $subscriptionRevenue + $purchaseRevenue + $renewalRevenue;
        $netRevenue = $totalRevenue - $refundAmount;

        // 创建或更新结算记录
        // 注意：RevenueSettlement 模型的金额字段 Cast 统一将传入值当作元处理
        // 所以这里需要将分转换为元（除以 100），Cast 会自动转换为分存储
        // 使用 firstOrNew + fill + save 避免 updateOrCreate 的重复转换问题
        $settlement = RevenueSettlement::firstOrNew(['settlement_date' => $date]);

        $settlement->fill([
            'recharge_count' => $rechargeCount,
            'recharge_amount' => $rechargeAmount / 100, // 转换为元，Cast 会转换为分
            'subscription_count' => $subscriptionCount,
            'subscription_revenue' => $subscriptionRevenue / 100, // 转换为元
            'purchase_count' => $purchaseCount,
            'purchase_revenue' => $purchaseRevenue / 100, // 转换为元
            'renewal_count' => $renewalCount,
            'renewal_revenue' => $renewalRevenue / 100, // 转换为元
            'refund_count' => $refundCount,
            'refund_amount' => $refundAmount / 100, // 转换为元
            'total_revenue' => $totalRevenue / 100, // 转换为元
            'net_revenue' => $netRevenue / 100, // 转换为元
            'currency' => config('currency.default', 'USD'), // 使用配置的默认货币
            'gateway_breakdown' => $gatewayBreakdown,
            'status' => 1, // 待确认
        ]);

        $settlement->save();

        return $settlement;
    }

    /**
     * 获取支付平台名称.
     */
    protected function getPlatformName(PayPlatform $platform): string
    {
        return $platform->identifier();
    }

    /**
     * 确认结算.
     */
    public function confirmSettlement(string $settlementId): bool
    {
        $settlement = RevenueSettlement::findOrFail($settlementId);

        $settlement->status = 2; // 已确认
        $settlement->confirmed_at = now()->format('Y-m-d H:i:s'); // 直接传入 datetime 字符串

        return $settlement->save();
    }
}
