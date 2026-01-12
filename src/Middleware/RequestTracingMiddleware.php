<?php

declare(strict_types=1);

namespace XditnModule\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 请求追踪中间件.
 *
 * 为每个请求生成唯一的追踪 ID，便于日志追踪和问题排查。
 *
 * 使用示例：
 * ```php
 * // 在路由中使用
 * Route::middleware(RequestTracingMiddleware::class)->group(function () {
 *     Route::get('users', [UserController::class, 'index']);
 * });
 *
 * // 在代码中获取追踪 ID
 * $traceId = Context::get('trace_id');
 *
 * // 或者使用助手函数
 * $traceId = request()->attributes->get('trace_id');
 * ```
 *
 * 配置：
 * ```php
 * // config/xditn.php
 * 'tracing' => [
 *     'enabled' => true,
 *     'header' => 'X-Trace-Id',
 *     'log_requests' => true,
 * ],
 * ```
 */
class RequestTracingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isEnabled()) {
            return $next($request);
        }

        $traceId = $this->resolveTraceId($request);

        // 存储到上下文
        Context::add('trace_id', $traceId);

        // 存储到请求属性
        $request->attributes->set('trace_id', $traceId);

        // 记录请求开始
        $startTime = microtime(true);

        if ($this->shouldLogRequests()) {
            $this->logRequestStart($request, $traceId);
        }

        $response = $next($request);

        // 添加追踪 ID 到响应头
        $response->headers->set($this->getHeaderName(), $traceId);

        // 记录请求结束
        if ($this->shouldLogRequests()) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logRequestEnd($request, $response, $traceId, $duration);
        }

        return $response;
    }

    /**
     * 检查是否启用追踪.
     */
    protected function isEnabled(): bool
    {
        return config('xditn.tracing.enabled', true);
    }

    /**
     * 获取追踪 ID 头名称.
     */
    protected function getHeaderName(): string
    {
        return config('xditn.tracing.header', 'X-Trace-Id');
    }

    /**
     * 是否记录请求日志.
     */
    protected function shouldLogRequests(): bool
    {
        return config('xditn.tracing.log_requests', false);
    }

    /**
     * 解析追踪 ID.
     *
     * 优先使用请求头中的追踪 ID，否则生成新的。
     */
    protected function resolveTraceId(Request $request): string
    {
        $headerName = $this->getHeaderName();

        return $request->header($headerName) ?? $this->generateTraceId();
    }

    /**
     * 生成追踪 ID.
     */
    protected function generateTraceId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * 记录请求开始.
     */
    protected function logRequestStart(Request $request, string $traceId): void
    {
        Log::info('Request started', [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * 记录请求结束.
     */
    protected function logRequestEnd(Request $request, Response $response, string $traceId, float $duration): void
    {
        Log::info('Request completed', [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);
    }
}
