<?php

declare(strict_types=1);

namespace XditnModule\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API 响应压缩中间件.
 *
 * 对 API 响应进行 Gzip 压缩，减少传输数据量。
 *
 * 使用示例：
 * ```php
 * // 在路由中使用
 * Route::middleware(CompressResponseMiddleware::class)->group(function () {
 *     Route::get('users', [UserController::class, 'index']);
 * });
 * ```
 *
 * 配置：
 * ```php
 * // config/xditn.php
 * 'compression' => [
 *     'enabled' => true,
 *     'min_size' => 1024, // 最小压缩大小（字节）
 *     'level' => 6, // 压缩级别 1-9
 * ],
 * ```
 */
class CompressResponseMiddleware
{
    /**
     * 可压缩的内容类型.
     *
     * @var array<int, string>
     */
    protected array $compressibleTypes = [
        'application/json',
        'application/xml',
        'text/html',
        'text/plain',
        'text/xml',
        'text/css',
        'text/javascript',
        'application/javascript',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCompress($request, $response)) {
            return $this->compressResponse($response);
        }

        return $response;
    }

    /**
     * 判断是否应该压缩响应.
     */
    protected function shouldCompress(Request $request, Response $response): bool
    {
        // 检查是否启用压缩
        if (!$this->isEnabled()) {
            return false;
        }

        // 检查客户端是否支持 gzip
        if (!$this->clientSupportsGzip($request)) {
            return false;
        }

        // 检查响应是否已经压缩
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // 检查内容类型是否可压缩
        if (!$this->isCompressibleContentType($response)) {
            return false;
        }

        // 检查内容大小是否达到最小压缩阈值
        if (!$this->meetsMinimumSize($response)) {
            return false;
        }

        return true;
    }

    /**
     * 检查是否启用压缩.
     */
    protected function isEnabled(): bool
    {
        return config('xditn.compression.enabled', false);
    }

    /**
     * 检查客户端是否支持 Gzip.
     */
    protected function clientSupportsGzip(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');

        return str_contains($acceptEncoding, 'gzip');
    }

    /**
     * 检查内容类型是否可压缩.
     */
    protected function isCompressibleContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        foreach ($this->compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查内容大小是否达到最小压缩阈值.
     */
    protected function meetsMinimumSize(Response $response): bool
    {
        $minSize = config('xditn.compression.min_size', 1024);
        $content = $response->getContent();

        return $content !== false && strlen($content) >= $minSize;
    }

    /**
     * 压缩响应内容.
     */
    protected function compressResponse(Response $response): Response
    {
        $content = $response->getContent();

        if ($content === false) {
            return $response;
        }

        $level = config('xditn.compression.level', 6);
        $compressed = gzencode($content, $level);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
