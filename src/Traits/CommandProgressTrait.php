<?php

declare(strict_types=1);

namespace XditnModule\Traits;

use Closure;
use Illuminate\Support\Collection;

/**
 * 命令行进度条 Trait.
 *
 * 为命令行命令添加进度条显示功能。
 *
 * 使用示例：
 * ```php
 * class MigrateCommand extends Command
 * {
 *     use CommandProgressTrait;
 *
 *     public function handle()
 *     {
 *         $items = $this->getMigrations();
 *
 *         // 方式一：使用 withProgress
 *         $this->withProgress($items, function ($item) {
 *             $this->runMigration($item);
 *         }, '运行迁移');
 *
 *         // 方式二：手动控制
 *         $this->startProgress(count($items), '处理中...');
 *         foreach ($items as $item) {
 *             $this->processItem($item);
 *             $this->advanceProgress();
 *         }
 *         $this->finishProgress('处理完成！');
 *     }
 * }
 * ```
 */
trait CommandProgressTrait
{
    /**
     * 使用进度条执行批量操作.
     *
     * @param iterable<mixed>|Collection $items 要处理的项目集合
     * @param Closure $callback 处理每个项目的回调函数
     * @param string $message 进度条消息
     *
     * @return array<int, mixed> 处理结果
     */
    protected function withProgress(iterable $items, Closure $callback, string $message = '处理中'): array
    {
        $items = $items instanceof Collection ? $items->all() : (is_array($items) ? $items : iterator_to_array($items));

        $count = count($items);
        $results = [];

        if ($count === 0) {
            $this->info('没有需要处理的项目');

            return $results;
        }

        $this->startProgress($count, $message);

        foreach ($items as $key => $item) {
            try {
                $results[$key] = $callback($item, $key);
            } catch (\Throwable $e) {
                $results[$key] = ['error' => $e->getMessage()];
                $this->newLine();
                $this->error("处理失败: {$e->getMessage()}");
            }

            $this->advanceProgress();
        }

        $this->finishProgress();

        return $results;
    }

    /**
     * 开始进度条.
     *
     * @param int $total 总数
     * @param string $message 消息
     */
    protected function startProgress(int $total, string $message = '处理中'): void
    {
        $this->info($message);
        $this->output->progressStart($total);
    }

    /**
     * 推进进度条.
     *
     * @param int $step 步进数
     */
    protected function advanceProgress(int $step = 1): void
    {
        $this->output->progressAdvance($step);
    }

    /**
     * 结束进度条.
     *
     * @param string|null $message 完成消息
     */
    protected function finishProgress(?string $message = null): void
    {
        $this->output->progressFinish();

        if ($message) {
            $this->info($message);
        }
    }

    /**
     * 显示带进度的表格.
     *
     * @param array<int, mixed> $items
     * @param Closure $rowCallback 返回表格行数据的回调
     * @param array<int, string> $headers 表头
     */
    protected function progressTable(array $items, Closure $rowCallback, array $headers): void
    {
        $rows = [];

        $this->withProgress($items, function ($item, $key) use (&$rows, $rowCallback) {
            $rows[] = $rowCallback($item, $key);

            return $item;
        });

        $this->newLine();
        $this->table($headers, $rows);
    }
}
