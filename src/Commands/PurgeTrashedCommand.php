<?php

declare(strict_types=1);

namespace XditnModule\Commands;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use XditnModule\Traits\CommandProgressTrait;
use XditnModule\XditnModule;

/**
 * 清理软删除数据命令.
 *
 * 永久删除超过指定天数的软删除记录。
 *
 * 使用示例：
 * ```bash
 * # 清理所有模块中超过30天的软删除数据
 * php artisan xditn:module:purge-trashed
 *
 * # 清理超过60天的数据
 * php artisan xditn:module:purge-trashed --days=60
 *
 * # 只清理指定模块
 * php artisan xditn:module:purge-trashed --module=Pay
 *
 * # 预览模式（不实际删除）
 * php artisan xditn:module:purge-trashed --dry-run
 * ```
 */
class PurgeTrashedCommand extends XditnModuleCommand
{
    use CommandProgressTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:purge-trashed
                            {--days=30 : Days after which to purge trashed records}
                            {--module= : Specific module to purge}
                            {--dry-run : Preview without actually deleting}
                            {--force : Force purge without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted records older than specified days';

    /**
     * @var int
     */
    protected int $totalDeleted = 0;

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $moduleName = $this->option('module');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        // 获取配置
        $configEnabled = config('xditn.soft_delete.auto_purge', true);
        $configDays = config('xditn.soft_delete.purge_after_days', 30);

        if (!$configEnabled && !$force) {
            $this->error('Soft delete auto purge is disabled in config. Use --force to override.');

            return self::FAILURE;
        }

        $days = $days ?: $configDays;

        $this->info("Purging trashed records older than {$days} days...");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        // 确认操作
        if (!$isDryRun && !$force) {
            if (!$this->confirm('This will permanently delete data. Continue?')) {
                return self::SUCCESS;
            }
        }

        // 获取所有模型
        $models = $this->getModelsWithSoftDeletes($moduleName);

        if (empty($models)) {
            $this->info('No models with soft deletes found.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($models).' models with soft deletes.');
        $this->newLine();

        $results = [];
        foreach ($models as $modelClass) {
            $count = $this->purgeModel($modelClass, $days, $isDryRun);
            if ($count > 0) {
                $results[] = [
                    'model' => class_basename($modelClass),
                    'count' => $count,
                    'status' => $isDryRun ? 'Would delete' : 'Deleted',
                ];
            }
        }

        // 显示结果
        if (!empty($results)) {
            $this->newLine();
            $this->table(['Model', 'Records', 'Status'], $results);
        }

        $this->newLine();
        $statusText = $isDryRun ? 'would be' : 'have been';
        $this->info("Total: {$this->totalDeleted} records {$statusText} permanently deleted.");

        return self::SUCCESS;
    }

    /**
     * 获取所有使用软删除的模型.
     *
     * @return array<int, string>
     */
    protected function getModelsWithSoftDeletes(?string $moduleName = null): array
    {
        $models = [];
        $modules = $moduleName ? [$moduleName] : $this->getModuleNames();

        foreach ($modules as $module) {
            $modelPath = XditnModule::getModulePath($module).'Models';

            if (!File::isDirectory($modelPath)) {
                continue;
            }

            $files = File::allFiles($modelPath);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $this->getModelClassName($module, $file->getFilenameWithoutExtension());

                if (!class_exists($className)) {
                    continue;
                }

                // 检查是否使用软删除
                if ($this->usesSoftDeletes($className)) {
                    $models[] = $className;
                }
            }
        }

        return $models;
    }

    /**
     * 获取所有模块名称.
     *
     * @return array<int, string>
     */
    protected function getModuleNames(): array
    {
        $modulesPath = XditnModule::moduleRootPath();
        $directories = File::directories($modulesPath);

        return array_map(fn ($dir) => basename($dir), $directories);
    }

    /**
     * 获取模型完整类名.
     */
    protected function getModelClassName(string $module, string $modelName): string
    {
        return XditnModule::getModuleModelNamespace($module).$modelName;
    }

    /**
     * 检查类是否使用软删除.
     */
    protected function usesSoftDeletes(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $traits = class_uses_recursive($className);

        return in_array(SoftDeletes::class, $traits);
    }

    /**
     * 清理指定模型的软删除数据.
     */
    protected function purgeModel(string $modelClass, int $days, bool $isDryRun): int
    {
        $cutoffDate = now()->subDays($days);

        // 获取要删除的记录数
        $query = $modelClass::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count > 0 && !$isDryRun) {
            // 永久删除
            $modelClass::onlyTrashed()
                ->where('deleted_at', '<', $cutoffDate)
                ->forceDelete();
        }

        $this->totalDeleted += $count;

        return $count;
    }
}
