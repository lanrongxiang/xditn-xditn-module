<?php

declare(strict_types=1);

namespace Modules\System\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\System\Models\ConnectorLog;
use Modules\System\Models\SystemCronTasksLog;

/**
 * 清理日志命令.
 *
 * 清理过期的 SystemCronTasksLog 和 ConnectorLog 记录
 */
class CleanupLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cleanup-logs 
                            {--cron-days=90 : 定时任务日志保留天数（默认90天）}
                            {--connector-days=30 : 接口日志保留天数（默认30天）}
                            {--dry-run : 仅显示将要删除的记录数，不实际删除}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理过期的系统日志（定时任务日志和接口日志）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cronDays = (int) $this->option('cron-days');
        $connectorDays = (int) $this->option('connector-days');
        $dryRun = $this->option('dry-run');

        $this->info('开始清理过期日志...');
        $this->info('');

        // 清理定时任务日志
        $this->cleanupCronTasksLog($cronDays, $dryRun);

        // 清理接口日志
        $this->cleanupConnectorLog($connectorDays, $dryRun);

        $this->info('');
        $this->info('✅ 日志清理完成！');

        return Command::SUCCESS;
    }

    /**
     * 清理定时任务日志.
     *
     * @param int $days 保留天数
     * @param bool $dryRun 是否仅预览
     */
    protected function cleanupCronTasksLog(int $days, bool $dryRun): void
    {
        $cutoffDate = Carbon::now()->subDays($days);
        // SystemCronTasksLog 使用 XditnModuleModel，created_at 是 unsignedInteger (Unix 时间戳)
        $cutoffTimestamp = $cutoffDate->timestamp;

        // 统计要删除的记录数
        $count = SystemCronTasksLog::where('created_at', '<', $cutoffTimestamp)->count();

        if ($count === 0) {
            $this->info("📋 定时任务日志：无需清理（保留 {$days} 天）");

            return;
        }

        if ($dryRun) {
            $this->warn("📋 定时任务日志：将删除 {$count} 条记录（创建时间早于 {$cutoffDate->format('Y-m-d H:i:s')}）");

            return;
        }

        // 执行删除（使用软删除）
        $deleted = SystemCronTasksLog::where('created_at', '<', $cutoffTimestamp)->delete();

        $this->info("📋 定时任务日志：已删除 {$deleted} 条记录（保留 {$days} 天）");
    }

    /**
     * 清理接口日志.
     *
     * @param int $days 保留天数
     * @param bool $dryRun 是否仅预览
     */
    protected function cleanupConnectorLog(int $days, bool $dryRun): void
    {
        $cutoffDate = Carbon::now()->subDays($days);
        // ConnectorLog 使用 XditnModuleModel，created_at 是 unsignedInteger (Unix 时间戳)
        $cutoffTimestamp = $cutoffDate->timestamp;

        // 统计要删除的记录数
        $count = ConnectorLog::where('created_at', '<', $cutoffTimestamp)->count();

        if ($count === 0) {
            $this->info("📋 接口日志：无需清理（保留 {$days} 天）");

            return;
        }

        if ($dryRun) {
            $this->warn("📋 接口日志：将删除 {$count} 条记录（创建时间早于 {$cutoffDate->format('Y-m-d H:i:s')}）");

            return;
        }

        // 执行删除（使用软删除）
        $deleted = ConnectorLog::where('created_at', '<', $cutoffTimestamp)->delete();

        $this->info("📋 接口日志：已删除 {$deleted} 条记录（保留 {$days} 天）");
    }
}
