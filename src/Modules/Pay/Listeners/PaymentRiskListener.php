<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use Modules\Pay\Events\PaymentCreated;
use Modules\VideoSubscription\Models\AntiFraudLog;

/**
 * 支付风控监听器.
 */
class PaymentRiskListener
{
    /**
     * 处理支付订单创建事件.
     */
    public function handle(PaymentCreated $event): void
    {
        try {
            $userId = $event->request['user_id'] ?? null;
            $orderId = $event->request['order_no'] ?? $event->request['order_id'] ?? null;
            if (!$userId || !$orderId) {
                return;
            }

            // 使用 anti_fraud_logs 表记录风控日志
            $riskLevel = $this->calculateRiskLevel($userId, $event->request['ip'] ?? null, $event->request['amount'] ?? 0);
            AntiFraudLog::create([
                'user_id' => $userId,
                'ip_address' => $event->request['ip'] ?? null,
                'user_agent' => $event->request['user_agent'] ?? null,
                'action_type' => 'payment_created',
                'request_data' => [
                    'order_id' => $orderId,
                    'amount' => $event->request['amount'] ?? 0,
                    'platform' => $event->platform,
                ],
                'risk_level' => $this->convertRiskLevelToInt($riskLevel),
                'status' => 1,
            ]);

            // 高风险记录警告日志
            if ($riskLevel === 'high') {
                \Log::channel('payment')->warning('高风险支付', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);
            }
        } catch (\Throwable $e) {
            // 静默失败，不影响主流程
        }
    }

    /**
     * 计算风险等级.
     *
     * @param string|null $userId 用户ID（UUID）
     * @param string|null $ip IP地址
     * @param int $amount 金额
     */
    protected function calculateRiskLevel(?string $userId, ?string $ip, int $amount): string
    {
        // 检查同一IP短时间内多次支付
        if ($ip) {
            $recentCount = AntiFraudLog::where('ip_address', $ip)
                ->where('action_type', 'payment_created')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($recentCount >= 5) {
                return 'high';
            }
        }

        // 检查同一用户短时间内多次支付
        if ($userId) {
            $recentCount = AntiFraudLog::where('user_id', $userId)
                ->where('action_type', 'payment_created')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($recentCount >= 3) {
                return 'medium';
            }
        }

        // 检查大额支付
        if ($amount > 100000) { // 大于1000元
            return 'medium';
        }

        return 'low';
    }

    /**
     * 转换风险等级为整数（1=低,2=中,3=高）.
     */
    protected function convertRiskLevelToInt(string $riskLevel): int
    {
        return match ($riskLevel) {
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }
}
