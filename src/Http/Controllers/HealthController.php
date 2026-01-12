<?php

declare(strict_types=1);

namespace XditnModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * 健康检查控制器.
 *
 * 提供系统健康状态检查 API，用于监控和负载均衡器健康检查。
 *
 * 使用示例：
 * ```php
 * // 在路由中注册
 * Route::get('health', [HealthController::class, 'index']);
 * Route::get('health/ready', [HealthController::class, 'ready']);
 * Route::get('health/live', [HealthController::class, 'live']);
 * ```
 */
class HealthController extends Controller
{
    /**
     * 获取完整健康状态.
     *
     * GET /api/health
     */
    public function index(): JsonResponse
    {
        $checks = $this->runHealthChecks();
        $isHealthy = $this->isOverallHealthy($checks);

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ], $isHealthy ? 200 : 503);
    }

    /**
     * 就绪检查（Kubernetes readiness probe）.
     *
     * 检查应用是否准备好接收流量。
     *
     * GET /api/health/ready
     */
    public function ready(): JsonResponse
    {
        $checks = $this->runHealthChecks();
        $isReady = $this->isOverallHealthy($checks);

        return response()->json([
            'status' => $isReady ? 'ready' : 'not_ready',
            'timestamp' => now()->toIso8601String(),
        ], $isReady ? 200 : 503);
    }

    /**
     * 存活检查（Kubernetes liveness probe）.
     *
     * 检查应用进程是否正常运行。
     *
     * GET /api/health/live
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 运行所有健康检查.
     *
     * @return array<string, array{status: string, message?: string, latency_ms?: float}>
     */
    protected function runHealthChecks(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }

    /**
     * 检查数据库连接.
     *
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查缓存连接.
     *
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_'.time();
            Cache::put($key, true, 10);
            $result = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($result !== true) {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write failed',
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查 Redis 连接.
     *
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    protected function checkRedis(): array
    {
        try {
            // 检查 Redis 是否配置
            if (config('database.redis.client') === null) {
                return [
                    'status' => 'skipped',
                    'message' => 'Redis not configured',
                ];
            }

            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查队列连接.
     *
     * @return array{status: string, message?: string}
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');

            if ($connection === 'sync') {
                return [
                    'status' => 'skipped',
                    'message' => 'Queue using sync driver',
                ];
            }

            Queue::size();

            return [
                'status' => 'ok',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查存储可写性.
     *
     * @return array{status: string, message?: string}
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path('app/.health_check');
            $written = file_put_contents($path, 'ok');

            if ($written === false) {
                return [
                    'status' => 'error',
                    'message' => 'Storage not writable',
                ];
            }

            unlink($path);

            return [
                'status' => 'ok',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查整体是否健康.
     *
     * @param array<string, array{status: string}> $checks
     */
    protected function isOverallHealthy(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                return false;
            }
        }

        return true;
    }
}
