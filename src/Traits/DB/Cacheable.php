<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cacheable Trait.
 *
 * 为模型/服务类提供统一的缓存处理能力
 * - 自动管理缓存键前缀
 * - 支持缓存标签（Tags）
 * - 支持自定义 TTL
 * - 支持缓存清理
 *
 * 使用方法：
 * 1. 在模型/服务类中 use Cacheable
 * 2. 可选：设置 $cachePrefix 自定义缓存前缀
 * 3. 可选：设置 $cacheTTL 自定义缓存时间（秒）
 * 4. 可选：设置 $cacheTags 启用缓存标签（需要支持 Tags 的缓存驱动如 Redis）
 *
 * 示例：
 * class UserService
 * {
 *     use Cacheable;
 *
 *     protected ?string $cachePrefix = 'user';
 *     protected int $cacheTTL = 3600;
 *     protected array $cacheTags = ['users'];
 *
 *     public function getUser(int $id): ?User
 *     {
 *         return $this->remember("user:{$id}", fn () => User::find($id));
 *     }
 * }
 */
trait Cacheable
{
    /**
     * 缓存时间（秒）
     * 默认 1 小时.
     */
    protected int $cacheTTL = 3600;

    /**
     * 缓存键前缀
     * 为 null 时自动使用类名.
     */
    protected ?string $cachePrefix = null;

    /**
     * 缓存标签
     * 为空数组时不使用标签
     * 注意：需要缓存驱动支持标签（如 Redis、Memcached）.
     *
     * @var array<int, string>
     */
    protected array $cacheTags = [];

    /**
     * 获取缓存前缀.
     */
    public function getCachePrefix(): string
    {
        if ($this->cachePrefix !== null) {
            return $this->cachePrefix;
        }

        // 如果是 Model，使用表名
        if (method_exists($this, 'getTable')) {
            return $this->getTable();
        }

        // 否则使用类名（短名称）
        return class_basename(static::class);
    }

    /**
     * 设置缓存前缀.
     *
     * @return $this
     */
    public function setCachePrefix(string $prefix): static
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * 获取缓存 TTL.
     */
    public function getCacheTTL(): int
    {
        return $this->cacheTTL;
    }

    /**
     * 设置缓存 TTL.
     *
     * @return $this
     */
    public function setCacheTTL(int $ttl): static
    {
        $this->cacheTTL = $ttl;

        return $this;
    }

    /**
     * 获取缓存标签.
     *
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        if (!empty($this->cacheTags)) {
            return $this->cacheTags;
        }

        return [$this->getCachePrefix()];
    }

    /**
     * 设置缓存标签.
     *
     * @param array<int, string> $tags
     *
     * @return $this
     */
    public function setCacheTags(array $tags): static
    {
        $this->cacheTags = $tags;

        return $this;
    }

    /**
     * 生成完整的缓存键.
     */
    protected function makeCacheKey(string $key): string
    {
        $prefix = config('xditn.admin_cache_key', 'xditn');

        return sprintf('%s:%s:%s', $prefix, $this->getCachePrefix(), $key);
    }

    /**
     * 获取缓存实例（带标签或不带标签）.
     *
     * @return \Illuminate\Contracts\Cache\Repository|\Illuminate\Cache\TaggedCache
     */
    protected function getCacheStore()
    {
        $tags = $this->getCacheTags();

        // 检查是否支持标签
        if (!empty($tags) && $this->supportsCacheTags()) {
            return Cache::tags($tags);
        }

        return Cache::store();
    }

    /**
     * 检查当前缓存驱动是否支持标签.
     */
    protected function supportsCacheTags(): bool
    {
        $driver = config('cache.default');
        $supportedDrivers = ['redis', 'memcached', 'dynamodb'];

        return in_array($driver, $supportedDrivers, true);
    }

    /**
     * 缓存数据（如果不存在则执行回调并缓存结果）.
     *
     * @param string $key 缓存键（不含前缀）
     * @param Closure $callback 获取数据的回调函数
     * @param int|null $ttl 缓存时间（秒），为 null 时使用默认值
     */
    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->makeCacheKey($key);
        $ttl = $ttl ?? $this->cacheTTL;

        return $this->getCacheStore()->remember($cacheKey, $ttl, $callback);
    }

    /**
     * 永久缓存数据（如果不存在则执行回调并缓存结果）.
     *
     * @param string $key 缓存键（不含前缀）
     * @param Closure $callback 获取数据的回调函数
     */
    public function rememberForever(string $key, Closure $callback): mixed
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->rememberForever($cacheKey, $callback);
    }

    /**
     * 获取缓存数据.
     *
     * @param string $key 缓存键（不含前缀）
     * @param mixed $default 默认值
     */
    public function getCache(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->get($cacheKey, $default);
    }

    /**
     * 设置缓存数据.
     *
     * @param string $key 缓存键（不含前缀）
     * @param mixed $value 缓存值
     * @param int|null $ttl 缓存时间（秒），为 null 时使用默认值
     */
    public function putCache(string $key, mixed $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->makeCacheKey($key);
        $ttl = $ttl ?? $this->cacheTTL;

        return $this->getCacheStore()->put($cacheKey, $value, $ttl);
    }

    /**
     * 永久设置缓存数据.
     *
     * @param string $key 缓存键（不含前缀）
     * @param mixed $value 缓存值
     */
    public function putCacheForever(string $key, mixed $value): bool
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->forever($cacheKey, $value);
    }

    /**
     * 删除指定缓存.
     *
     * @param string $key 缓存键（不含前缀）
     */
    public function forgetCache(string $key): bool
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->forget($cacheKey);
    }

    /**
     * 删除多个缓存.
     *
     * @param array<int, string> $keys 缓存键数组（不含前缀）
     */
    public function forgetCaches(array $keys): void
    {
        foreach ($keys as $key) {
            $this->forgetCache($key);
        }
    }

    /**
     * 清空当前标签下的所有缓存.
     * 注意：需要缓存驱动支持标签.
     */
    public function flushCache(): bool
    {
        if ($this->supportsCacheTags()) {
            return Cache::tags($this->getCacheTags())->flush();
        }

        // 不支持标签时，无法批量清除
        return false;
    }

    /**
     * 检查缓存是否存在.
     *
     * @param string $key 缓存键（不含前缀）
     */
    public function hasCache(string $key): bool
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->has($cacheKey);
    }

    /**
     * 增加缓存值（原子操作）.
     *
     * @param string $key 缓存键（不含前缀）
     * @param int $value 增加的值
     */
    public function incrementCache(string $key, int $value = 1): int|bool
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->increment($cacheKey, $value);
    }

    /**
     * 减少缓存值（原子操作）.
     *
     * @param string $key 缓存键（不含前缀）
     * @param int $value 减少的值
     */
    public function decrementCache(string $key, int $value = 1): int|bool
    {
        $cacheKey = $this->makeCacheKey($key);

        return $this->getCacheStore()->decrement($cacheKey, $value);
    }

    /**
     * 带锁的缓存操作（防止缓存穿透/击穿）.
     *
     * @param string $key 缓存键（不含前缀）
     * @param Closure $callback 获取数据的回调函数
     * @param int|null $ttl 缓存时间（秒）
     * @param int $lockSeconds 锁定时间（秒）
     */
    public function rememberWithLock(string $key, Closure $callback, ?int $ttl = null, int $lockSeconds = 10): mixed
    {
        $cacheKey = $this->makeCacheKey($key);
        $ttl = $ttl ?? $this->cacheTTL;

        // 先尝试获取缓存
        $value = $this->getCacheStore()->get($cacheKey);
        if ($value !== null) {
            return $value;
        }

        // 获取锁，防止并发请求同时查询数据库
        $lock = Cache::lock($cacheKey.':lock', $lockSeconds);

        try {
            // 阻塞等待获取锁
            $lock->block($lockSeconds);

            // 再次检查缓存（可能其他请求已经设置了缓存）
            $value = $this->getCacheStore()->get($cacheKey);
            if ($value !== null) {
                return $value;
            }

            // 执行回调获取数据
            $value = $callback();

            // 设置缓存
            $this->getCacheStore()->put($cacheKey, $value, $ttl);

            return $value;
        } finally {
            $lock->release();
        }
    }
}
