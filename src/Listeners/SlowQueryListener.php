<?php

declare(strict_types=1);

namespace XditnModule\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

/**
 * 慢查询监听器.
 *
 * 记录执行时间超过阈值的数据库查询。
 *
 * 在 EventServiceProvider 中注册：
 * ```php
 * protected $listen = [
 *     QueryExecuted::class => [
 *         SlowQueryListener::class,
 *     ],
 * ];
 * ```
 *
 * 或者在 boot 方法中：
 * ```php
 * if (config('xditn.query_log.enabled', false)) {
 *     DB::listen(function (QueryExecuted $query) {
 *         (new SlowQueryListener)->handle($query);
 *     });
 * }
 * ```
 */
class SlowQueryListener
{
    /**
     * Handle the event.
     */
    public function handle(QueryExecuted $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $timeMs = $event->time;
        $threshold = $this->getThreshold();

        // 检查是否是慢查询
        if ($timeMs >= $threshold) {
            $this->logSlowQuery($event);
        }

        // 如果启用了所有查询日志
        if ($this->shouldLogAllQueries()) {
            $this->logQuery($event);
        }
    }

    /**
     * 检查是否启用查询日志.
     */
    protected function isEnabled(): bool
    {
        return config('xditn.query_log.enabled', false);
    }

    /**
     * 获取慢查询阈值（毫秒）.
     */
    protected function getThreshold(): float
    {
        return (float) config('xditn.query_log.slow_threshold', 1000);
    }

    /**
     * 获取日志通道.
     */
    protected function getLogChannel(): string
    {
        return config('xditn.query_log.log_channel', 'query');
    }

    /**
     * 是否记录所有查询.
     */
    protected function shouldLogAllQueries(): bool
    {
        return config('xditn.query_log.log_all', false);
    }

    /**
     * 记录慢查询.
     */
    protected function logSlowQuery(QueryExecuted $event): void
    {
        $sql = $this->formatSql($event->sql, $event->bindings);

        Log::channel($this->getLogChannel())->warning('Slow Query Detected', [
            'sql' => $sql,
            'time_ms' => round($event->time, 2),
            'threshold_ms' => $this->getThreshold(),
            'connection' => $event->connectionName,
            'trace' => $this->getTrace(),
        ]);
    }

    /**
     * 记录查询.
     */
    protected function logQuery(QueryExecuted $event): void
    {
        $sql = $this->formatSql($event->sql, $event->bindings);

        Log::channel($this->getLogChannel())->debug('Query Executed', [
            'sql' => $sql,
            'time_ms' => round($event->time, 2),
            'connection' => $event->connectionName,
        ]);
    }

    /**
     * 格式化 SQL（替换绑定参数）.
     *
     * @param array<int, mixed> $bindings
     */
    protected function formatSql(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            $value = $this->formatBinding($binding);
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }

    /**
     * 格式化绑定值.
     */
    protected function formatBinding(mixed $binding): string
    {
        if (is_null($binding)) {
            return 'NULL';
        }

        if (is_bool($binding)) {
            return $binding ? 'TRUE' : 'FALSE';
        }

        if (is_string($binding)) {
            return "'{$binding}'";
        }

        if ($binding instanceof \DateTimeInterface) {
            return "'{$binding->format('Y-m-d H:i:s')}'";
        }

        return (string) $binding;
    }

    /**
     * 获取调用栈跟踪.
     *
     * @return array<int, string>
     */
    protected function getTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $relevantTrace = [];

        foreach ($trace as $frame) {
            // 跳过框架内部调用
            if (isset($frame['file']) && !str_contains($frame['file'], 'vendor')) {
                $relevantTrace[] = ($frame['file'] ?? 'unknown').':'.($frame['line'] ?? 0);
            }
        }

        return array_slice($relevantTrace, 0, 5);
    }
}
