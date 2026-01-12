<?php

declare(strict_types=1);

namespace XditnModule\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API 版本控制中间件.
 *
 * 支持两种版本控制方式：
 * 1. URL 路径版本：/api/v1/users, /api/v2/users
 * 2. Header 版本：X-API-Version: 1 或 Accept: application/vnd.api+json;version=1
 *
 * 使用示例：
 * ```php
 * // 在路由中使用
 * Route::prefix('api/v1')->middleware(ApiVersionMiddleware::class)->group(function () {
 *     Route::get('users', [UserController::class, 'index']);
 * });
 *
 * // 在控制器中获取版本
 * $version = request()->attributes->get('api_version'); // 'v1'
 * ```
 */
class ApiVersionMiddleware
{
    /**
     * 默认 API 版本.
     */
    protected string $defaultVersion = 'v1';

    /**
     * 支持的 API 版本.
     *
     * @var array<int, string>
     */
    protected array $supportedVersions = ['v1', 'v2'];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->resolveVersion($request);

        // 将版本信息存储到请求属性中
        $request->attributes->set('api_version', $version);
        $request->attributes->set('api_version_number', $this->extractVersionNumber($version));

        // 在响应头中添加版本信息
        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-API-Version', $version);
        }

        return $response;
    }

    /**
     * 解析 API 版本.
     */
    protected function resolveVersion(Request $request): string
    {
        // 1. 优先从 URL 路径解析
        $pathVersion = $this->getVersionFromPath($request);
        if ($pathVersion) {
            return $pathVersion;
        }

        // 2. 从自定义 Header 解析
        $headerVersion = $this->getVersionFromHeader($request);
        if ($headerVersion) {
            return $headerVersion;
        }

        // 3. 从 Accept Header 解析
        $acceptVersion = $this->getVersionFromAcceptHeader($request);
        if ($acceptVersion) {
            return $acceptVersion;
        }

        // 4. 使用配置的默认版本
        return config('xditn.route.version', $this->defaultVersion);
    }

    /**
     * 从 URL 路径获取版本.
     */
    protected function getVersionFromPath(Request $request): ?string
    {
        $path = $request->path();

        // 匹配 /api/v1/, /api/v2/ 等模式
        if (preg_match('/\/?(api\/)?v(\d+)\//', $path, $matches)) {
            $version = 'v'.$matches[2];

            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * 从自定义 Header 获取版本.
     */
    protected function getVersionFromHeader(Request $request): ?string
    {
        $headerName = config('xditn.route.version_header', 'X-API-Version');
        $headerValue = $request->header($headerName);

        if ($headerValue) {
            $version = $this->normalizeVersion($headerValue);

            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * 从 Accept Header 获取版本.
     *
     * 支持格式：application/vnd.api+json;version=1
     */
    protected function getVersionFromAcceptHeader(Request $request): ?string
    {
        $accept = $request->header('Accept', '');

        if (preg_match('/version=(\d+)/', $accept, $matches)) {
            $version = 'v'.$matches[1];

            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * 规范化版本号.
     */
    protected function normalizeVersion(string $version): string
    {
        $version = trim($version);

        // 如果是纯数字，添加 'v' 前缀
        if (is_numeric($version)) {
            return 'v'.$version;
        }

        // 如果已经是 v1 格式，直接返回
        if (preg_match('/^v\d+$/', $version)) {
            return $version;
        }

        return $this->defaultVersion;
    }

    /**
     * 检查版本是否支持.
     */
    protected function isVersionSupported(string $version): bool
    {
        $supportedVersions = config('xditn.route.supported_versions', $this->supportedVersions);

        return in_array($version, $supportedVersions, true);
    }

    /**
     * 提取版本号（数字部分）.
     */
    protected function extractVersionNumber(string $version): int
    {
        if (preg_match('/v(\d+)/', $version, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }
}
