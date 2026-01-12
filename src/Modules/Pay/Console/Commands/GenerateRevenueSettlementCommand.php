<?php

declare(strict_types=1);

namespace Modules\Pay\Console\Commands;

use Illuminate\Console\Command;
use Modules\Pay\Services\RevenueSettlementService;
use Modules\VideoSubscription\Enums\PaymentGateway;
use Modules\VideoSubscription\Services\ReconciliationService;

/**
 * 生成收入结算命令.
 */
class GenerateRevenueSettlementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:generate-revenue-settlement {date?} {--no-reconciliation : 跳过自动对账}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成日收入结算报表（从 pay_orders 统计），并自动执行对账';

    public function __construct(
        protected RevenueSettlementService $revenueService,
        protected ReconciliationService $reconciliationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument('date') ?: date('Y-m-d', strtotime('-1 day'));
        $skipReconciliation = $this->option('no-reconciliation');

        $this->info("开始生成 {$date} 的收入结算...");

        try {
            $settlement = $this->revenueService->generateDailySettlement($date);

            $this->info('结算生成成功！');
            // 注意：模型的金额字段 getter 已经将分转换为元，所以这里不需要再除以 100
            $this->table(
                ['项目', '数量', '金额(USD)'],
                [
                    ['充值', $settlement->recharge_count, '$'.number_format($settlement->recharge_amount, 2)],
                    ['订阅', $settlement->subscription_count, '$'.number_format($settlement->subscription_revenue, 2)],
                    ['购买', $settlement->purchase_count, '$'.number_format($settlement->purchase_revenue, 2)],
                    ['续费', $settlement->renewal_count, '$'.number_format($settlement->renewal_revenue, 2)],
                    ['退款', $settlement->refund_count, '-$'.number_format($settlement->refund_amount, 2)],
                    ['总收入', '-', '$'.number_format($settlement->total_revenue, 2)],
                    ['净收入', '-', '$'.number_format($settlement->net_revenue, 2)],
                ]
            );

            // 自动对账
            if (!$skipReconciliation) {
                $this->performReconciliation($date, $settlement);
            } else {
                $this->info('已跳过自动对账（使用 --no-reconciliation 选项）');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('结算生成失败: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * 执行自动对账.
     *
     * @param string $date 结算日期
     * @param \Modules\Pay\Models\RevenueSettlement $settlement 结算记录
     */
    protected function performReconciliation(string $date, $settlement): void
    {
        $this->info('');
        $this->info('开始执行自动对账...');

        // 从 gateway_breakdown 中获取所有支付网关
        $gatewayBreakdown = $settlement->gateway_breakdown ?? [];
        if (empty($gatewayBreakdown)) {
            $this->warn('没有支付网关数据，跳过对账');

            return;
        }

        $allReconciled = true;
        $reconciliationResults = [];

        foreach ($gatewayBreakdown as $gatewayName => $data) {
            // 跳过没有交易的网关
            if (($data['count'] ?? 0) === 0) {
                continue;
            }

            // 从网关名称转换为 PaymentGateway 枚举
            $gateway = PaymentGateway::fromIdentifier($gatewayName);
            if (!$gateway) {
                $this->warn("未知的支付网关: {$gatewayName}，跳过对账");
                continue;
            }

            try {
                $this->info("对账 {$gateway->name()} - {$date}...");

                $reconciliation = $this->reconciliationService->reconcile($date, $gateway);

                $status = $reconciliation->status === 2 ? '已对账' : '有差异';
                $reconciliationResults[] = [
                    'gateway' => $gateway->name(),
                    'status' => $status,
                    'local_total' => '$'.number_format($reconciliation->local_total, 2),
                    'gateway_total' => '$'.number_format($reconciliation->gateway_total, 2),
                    'difference' => '$'.number_format($reconciliation->difference, 2),
                    'local_count' => $reconciliation->local_count,
                    'gateway_count' => $reconciliation->gateway_count,
                ];

                if ($reconciliation->status !== 2) {
                    $allReconciled = false;
                    $this->warn("  ⚠️  对账有差异: {$status}");

                    // 打印差异详情
                    $differenceDetails = $reconciliation->difference_details ?? [];
                    if (!empty($differenceDetails)) {
                        $this->warn('  差异详情:');
                        foreach ($differenceDetails as $detail) {
                            $type = $detail['type'] ?? 'unknown';
                            $typeName = match ($type) {
                                'amount_mismatch' => '金额不一致',
                                'missing_in_gateway' => '网关缺失',
                                'missing_in_local' => '本地缺失',
                                default => $type,
                            };

                            $message = "    - {$typeName}: ";
                            if ($type === 'amount_mismatch') {
                                // 金额已经是分，需要转换为元显示
                                $localAmount = number_format(($detail['local_amount'] ?? 0) / 100, 2);
                                $gatewayAmount = number_format(($detail['gateway_amount'] ?? 0) / 100, 2);
                                $message .= "订单 {$detail['order_id']} - 本地: \${$localAmount}, 网关: \${$gatewayAmount}";
                            } elseif ($type === 'missing_in_gateway') {
                                $message .= "订单 {$detail['order_id']} ({$detail['out_trade_no']}) 在网关中不存在";
                            } elseif ($type === 'missing_in_local') {
                                // 金额已经是分，需要转换为元显示
                                $gatewayAmount = number_format(($detail['gateway_amount'] ?? 0) / 100, 2);
                                $message .= "网关交易 {$detail['gateway_transaction_id']} (\${$gatewayAmount}) 在本地不存在";
                            } else {
                                $message .= json_encode($detail, JSON_UNESCAPED_UNICODE);
                            }

                            $this->warn($message);
                        }
                    }
                } else {
                    $this->info("  ✅ 对账成功: {$status}");
                }
            } catch (\Exception $e) {
                $allReconciled = false;
                $this->error("  ❌ 对账失败: {$e->getMessage()}");
                $reconciliationResults[] = [
                    'gateway' => $gateway->name(),
                    'status' => '对账失败',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // 显示对账结果汇总
        if (!empty($reconciliationResults)) {
            $this->info('');
            $this->table(
                ['支付网关', '状态', '本地总额', '网关总额', '差异', '本地交易数', '网关交易数'],
                array_map(function ($result) {
                    return [
                        $result['gateway'],
                        $result['status'] ?? '对账失败',
                        $result['local_total'] ?? '-',
                        $result['gateway_total'] ?? '-',
                        $result['difference'] ?? '-',
                        $result['local_count'] ?? '-',
                        $result['gateway_count'] ?? '-',
                    ];
                }, $reconciliationResults)
            );
        }

        // 如果执行了对账（无论是否有差异），都更新结算记录状态为"已对账"
        // 因为对账已经完成，只是结果可能有差异，需要管理员查看对账记录处理
        if (!empty($reconciliationResults)) {
            $settlement->update(['status' => 3]); // 3=已对账

            if ($allReconciled) {
                $this->info('');
                $this->info('✅ 所有支付网关对账成功，结算记录状态已更新为"已对账"');
            } else {
                $this->info('');
                $this->warn('⚠️  部分支付网关对账有差异，结算记录状态已更新为"已对账"');
                $this->warn('   差异详情已在上方显示，请查看对账记录处理差异');
            }
        }
    }
}
