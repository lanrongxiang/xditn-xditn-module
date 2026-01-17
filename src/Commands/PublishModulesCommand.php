<?php

declare(strict_types=1);

namespace XditnModule\Commands;

use Illuminate\Support\Facades\File;
use XditnModule\XditnModule;

/**
 * 发布模块到应用层.
 */
class PublishModulesCommand extends XditnModuleCommand
{
    protected $signature = 'xditn:module:publish 
                            {module? : 指定要发布的模块名称，不指定则发布所有模块}
                            {--force : 强制覆盖已存在的模块}
                            {--all : 发布所有模块}';

    protected $description = '发布框架模块到应用 modules 目录';

    /**
     * 执行命令.
     */
    public function handle(): int
    {
        $module = $this->argument('module');
        $force = $this->option('force');
        $all = $this->option('all');

        // 确保 modules 目录存在
        $targetPath = XditnModule::moduleRootPath();
        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        if ($all || !$module) {
            return $this->publishAllModules($force);
        }

        return $this->publishModule($module, $force);
    }

    /**
     * 发布所有模块.
     */
    protected function publishAllModules(bool $force): int
    {
        $sourcePath = XditnModule::packageModulesPath();

        if (!File::exists($sourcePath)) {
            $this->error('框架模块目录不存在: '.$sourcePath);

            return self::FAILURE;
        }

        $modules = File::directories($sourcePath);
        $publishedCount = 0;

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            if ($this->publishModule($moduleName, $force) === self::SUCCESS) {
                $publishedCount++;
            }
        }

        $this->newLine();
        $this->info("✓ 共发布 {$publishedCount} 个模块到 modules/ 目录");
        $this->newLine();
        $this->warn('重要提示：');
        $this->line('1. 发布后的模块优先于 vendor 中的模块加载');
        $this->line('2. 你可以自由修改 modules/ 目录下的代码');
        $this->line('3. 更新包后，使用 --force 参数可覆盖更新模块');
        $this->newLine();

        // 提示用户运行 composer dump-autoload
        $this->info('请运行以下命令更新自动加载：');
        $this->line('composer dump-autoload');

        return self::SUCCESS;
    }

    /**
     * 发布单个模块.
     */
    protected function publishModule(string $moduleName, bool $force): int
    {
        $moduleName = ucfirst($moduleName);
        $sourcePath = XditnModule::packageModulesPath().$moduleName;
        $targetPath = XditnModule::moduleRootPath().$moduleName;

        // 检查源模块是否存在
        if (!File::exists($sourcePath)) {
            $this->warn("模块 [{$moduleName}] 在框架中不存在，跳过");

            return self::FAILURE;
        }

        // 检查目标是否已存在
        if (File::exists($targetPath)) {
            if (!$force) {
                $this->warn("模块 [{$moduleName}] 已存在，使用 --force 覆盖");

                return self::FAILURE;
            }
            // 强制模式：先删除已有目录
            File::deleteDirectory($targetPath);
        }

        // 复制模块
        File::copyDirectory($sourcePath, $targetPath);
        $this->info("✓ 模块 [{$moduleName}] 已发布到 modules/{$moduleName}");

        return self::SUCCESS;
    }
}
