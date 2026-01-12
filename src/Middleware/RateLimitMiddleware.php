<?php

declare(strict_types=1);

namespace XditnModule\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use XditnModule\Exceptions\RateLimitException;

/**
 * API 速率限制中间件.
 *
 * 基于 IP 地址或用户 ID 进行请求频率限制，防止 API 滥用。
 *
 * 使用示例：
 * ```php
 * // 在路由中使用
 * Route::middleware(RateLimitMiddleware::class)->group(function () {
 *     Route::get('users', [UserController::class, 'index']);
 * });
 *
 * // 自定义限制参数
 * Route::middleware('rate_limit:100,1')->group(function () {
 *     // 每分钟最多 100 次请求
 * });
 * ```
 */
class RateLimitMiddleware
{
    public function __construct(
        protected RateLimiter $limiter
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     * @param int|null $maxAttempts 最大请求次数
     * @param int|null $decayMinutes 时间窗口（分钟）
     *
     * @throws RateLimitException
     */
    public function handle(Request $request, Closure $next, ?int $maxAttempts = null, ?int $decayMinutes = null): Response
    {
        if (!$this->isEnabled()) {
            return $next($request);
        }

        $maxAttempts = $maxAttempts ?? $this->getMaxAttempts();
        $decayMinutes = $decayMinutes ?? $this->getDecayMinutes();
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * 检查是否启用速率限制.
     */
    protected function isEnabled(): bool
    {
        return config('xditn.rate_limit.enabled', true);
    }

    /**
     * 获取最大请求次数.
     */
    protected function getMaxAttempts(): int
    {
        return config('xditn.rate_limit.max_attempts', 60);
    }

    /**
     * 获取时间窗口（分钟）.
     */
    protected function getDecayMinutes(): int
    {
        return config('xditn.rate_limit.decay_minutes', 1);
    }

    /**
     * 解析请求签名（用于标识唯一请求者）.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // 优先使用认证用户 ID
        if ($user = $request->user()) {
            return 'rate_limit:user:'.$user->getAuthIdentifier();
        }

        // 使用 IP 地址
        return 'rate_limit:ip:'.$request->ip();
    }

    /**
     * 计算剩余请求次数.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }

    /**
     * 添加速率限制响应头.
     */
    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        return $response;
    }

    /**
     * 构建超出限制的响应.
     *
     * @throws RateLimitException
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        throw new RateLimitException(
            message: '请求过于频繁，请稍后再试',
            retryAfter: $retryAfter,
            maxAttempts: $maxAttempts
        );
    }
}
