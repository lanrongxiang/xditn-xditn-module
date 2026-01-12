<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

/**
 * 创建模型观察者命令.
 *
 * 使用示例：
 * ```bash
 * php artisan xditn:module:make:observer Pay RechargeActivity
 * ```
 */
class Observer extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:observer {module} {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model observer for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $modelName = $this->getModelName();

        $observerPath = $this->getObserverPath($module);
        $observerFile = $observerPath.$this->getObserverFileName();

        // 确保目录存在
        if (!File::isDirectory($observerPath)) {
            File::makeDirectory($observerPath, 0755, true);
        }

        // 检查文件是否存在
        if (File::exists($observerFile)) {
            $answer = $this->ask($observerFile.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                return self::SUCCESS;
            }
        }

        // 生成观察者文件
        $content = $this->buildObserverContent($module, $modelName);
        File::put($observerFile, $content);

        if (File::exists($observerFile)) {
            $this->info($observerFile.' has been created');
            $this->newLine();
            $this->info('Don\'t forget to register the observer in your ServiceProvider:');
            $this->line("    {$modelName}::observe({$this->getObserverName()}::class);");
        } else {
            $this->error($observerFile.' create failed');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * 获取观察者目录路径.
     */
    protected function getObserverPath(string $module): string
    {
        return XditnModule::getModulePath($module).'Observers'.DIRECTORY_SEPARATOR;
    }

    /**
     * 获取观察者文件名.
     */
    protected function getObserverFileName(): string
    {
        return $this->getObserverName().'.php';
    }

    /**
     * 获取观察者类名.
     */
    protected function getObserverName(): string
    {
        return $this->getModelName().'Observer';
    }

    /**
     * 获取模型名称.
     */
    protected function getModelName(): string
    {
        return Str::of($this->argument('model'))
            ->studly()
            ->toString();
    }

    /**
     * 构建观察者文件内容.
     */
    protected function buildObserverContent(string $module, string $modelName): string
    {
        $namespace = XditnModule::getModuleNamespace($module).'\\Observers';
        $modelNamespace = XditnModule::getModuleModelNamespace($module);
        $modelClass = trim($modelNamespace, '\\').'\\'.$modelName;

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use {$modelClass};

/**
 * {$modelName} 观察者.
 *
 * 用于监听 {$modelName} 模型的生命周期事件。
 */
class {$this->getObserverName()}
{
    /**
     * 处理 {$modelName} "created" 事件.
     */
    public function created({$modelName} \$model): void
    {
        //
    }

    /**
     * 处理 {$modelName} "updated" 事件.
     */
    public function updated({$modelName} \$model): void
    {
        //
    }

    /**
     * 处理 {$modelName} "deleted" 事件.
     */
    public function deleted({$modelName} \$model): void
    {
        //
    }

    /**
     * 处理 {$modelName} "restored" 事件（软删除恢复）.
     */
    public function restored({$modelName} \$model): void
    {
        //
    }

    /**
     * 处理 {$modelName} "forceDeleted" 事件（永久删除）.
     */
    public function forceDeleted({$modelName} \$model): void
    {
        //
    }

    /**
     * 处理 {$modelName} "saving" 事件（创建或更新前）.
     */
    public function saving({$modelName} \$model): void
    {
        //
    }

    /**
     * 处理 {$modelName} "saved" 事件（创建或更新后）.
     */
    public function saved({$modelName} \$model): void
    {
        //
    }
}

PHP;
    }
}
