<?php

declare(strict_types=1);

namespace Modules\Pay\Console\Commands;

use Illuminate\Console\Command;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Models\Order;
use Modules\VideoSubscription\Services\CoinRechargeService;

/**
 * 取消超时订单命令.
 *
 * 自动取消超过支付超时时间的待支付订单
 */
class CancelTimeoutOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:cancel-timeout-orders {--limit=100 : 每次处理的最大订单数量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '取消超过支付超时时间的待支付订单';

    /**
     * Execute the console command.
     */
    public function handle(CoinRechargeService $rechargeService): int
    {
        $paymentTimeout = config('pay.general.payment_timeout', 30);

        // 如果未启用支付超时功能，直接返回
        if ($paymentTimeout <= 0) {
            $this->info('支付超时功能未启用（payment_timeout <= 0）');

            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $timeoutMinutes = $paymentTimeout;
        $timeoutDateTime = now()->subMinutes($timeoutMinutes);

        // 查询超时的待支付充值订单
        $timeoutOrders = Order::where('pay_status', PayStatus::PENDING->value)
            ->whereHas('rechargeOrder') // 只处理充值订单
            ->where('created_at', '<', $timeoutDateTime)
            ->limit($limit)
            ->get();

        if ($timeoutOrders->isEmpty()) {
            $this->info('没有需要取消的超时订单');

            return self::SUCCESS;
        }

        $this->info("找到 {$timeoutOrders->count()} 个超时订单，开始处理...");

        $successCount = 0;
        $failedCount = 0;

        foreach ($timeoutOrders as $order) {
            try {
                $rechargeService->cancelOrder($order, '支付超时自动取消');
                $successCount++;
                $this->line("订单 {$order->id} 已取消");
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("订单 {$order->id} 取消失败: {$e->getMessage()}");
            }
        }

        $this->info("处理完成: 成功 {$successCount} 个，失败 {$failedCount} 个");

        return self::SUCCESS;
    }
}
