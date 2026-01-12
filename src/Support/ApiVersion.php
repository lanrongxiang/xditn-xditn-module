<?php

declare(strict_types=1);

namespace XditnModule\Support;

use Illuminate\Support\Facades\Route;

/**
 * API 版本助手类.
 *
 * 提供便捷的 API 版本相关操作。
 *
 * 使用示例：
 * ```php
 * // 获取当前版本
 * $version = ApiVersion::current(); // 'v1'
 *
 * // 检查版本
 * if (ApiVersion::is('v1')) {
 *     // v1 版本逻辑
 * }
 *
 * // 版本比较
 * if (ApiVersion::gte('v2')) {
 *     // v2 及以上版本逻辑
 * }
 *
 * // 注册版本化路由
 * ApiVersion::routes('v1', function () {
 *     Route::get('users', [UserController::class, 'index']);
 * });
 * ```
 */
class ApiVersion
{
    /**
     * 获取当前 API 版本.
     */
    public static function current(): string
    {
        return request()->attributes->get('api_version', config('xditn.route.version', 'v1'));
    }

    /**
     * 获取当前 API 版本号（数字）.
     */
    public static function currentNumber(): int
    {
        return request()->attributes->get('api_version_number', 1);
    }

    /**
     * 检查当前版本是否等于指定版本.
     */
    public static function is(string $version): bool
    {
        return static::current() === static::normalize($version);
    }

    /**
     * 检查当前版本是否大于指定版本.
     */
    public static function gt(string $version): bool
    {
        return static::currentNumber() > static::extractNumber($version);
    }

    /**
     * 检查当前版本是否大于等于指定版本.
     */
    public static function gte(string $version): bool
    {
        return static::currentNumber() >= static::extractNumber($version);
    }

    /**
     * 检查当前版本是否小于指定版本.
     */
    public static function lt(string $version): bool
    {
        return static::currentNumber() < static::extractNumber($version);
    }

    /**
     * 检查当前版本是否小于等于指定版本.
     */
    public static function lte(string $version): bool
    {
        return static::currentNumber() <= static::extractNumber($version);
    }

    /**
     * 获取默认版本.
     */
    public static function default(): string
    {
        return config('xditn.route.version', 'v1');
    }

    /**
     * 获取所有支持的版本.
     *
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return config('xditn.route.supported_versions', ['v1', 'v2']);
    }

    /**
     * 检查版本是否被支持.
     */
    public static function isSupported(string $version): bool
    {
        return in_array(static::normalize($version), static::supported(), true);
    }

    /**
     * 规范化版本号.
     */
    public static function normalize(string $version): string
    {
        $version = trim($version);

        if (is_numeric($version)) {
            return 'v'.$version;
        }

        return $version;
    }

    /**
     * 提取版本号.
     */
    public static function extractNumber(string $version): int
    {
        if (preg_match('/v?(\d+)/', $version, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * 注册版本化路由组.
     *
     * @param string $version API 版本
     * @param callable $routes 路由定义回调
     * @param array<string, mixed> $options 额外选项
     */
    public static function routes(string $version, callable $routes, array $options = []): void
    {
        $prefix = config('xditn.route.prefix', 'api');
        $middlewares = config('xditn.route.middlewares', []);

        Route::prefix($prefix.'/'.$version)
            ->middleware(array_merge($middlewares, $options['middleware'] ?? []))
            ->name($options['name'] ?? $version.'.')
            ->group($routes);
    }

    /**
     * 根据版本执行不同的逻辑.
     *
     * @param array<string, callable> $handlers 版本处理器映射
     * @param callable|null $default 默认处理器
     *
     * @return mixed
     */
    public static function match(array $handlers, ?callable $default = null): mixed
    {
        $current = static::current();

        if (isset($handlers[$current])) {
            return $handlers[$current]();
        }

        // 尝试数字版本
        $currentNumber = static::currentNumber();
        if (isset($handlers[$currentNumber])) {
            return $handlers[$currentNumber]();
        }

        if ($default) {
            return $default();
        }

        return null;
    }
}
