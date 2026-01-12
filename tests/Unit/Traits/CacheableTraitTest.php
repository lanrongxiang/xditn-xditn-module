<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use XditnModule\Traits\DB\Cacheable;

class CacheableTraitTest extends TestCase
{
    protected CacheableTestClass $cacheableClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheableClass = new CacheableTestClass();

        // 清理测试缓存
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_get_cache_prefix_uses_custom_prefix(): void
    {
        $class = new CacheableTestClass();
        $class->setCachePrefix('custom_prefix');

        $this->assertEquals('custom_prefix', $class->getCachePrefix());
    }

    public function test_get_cache_prefix_uses_class_name_by_default(): void
    {
        $this->assertEquals('CacheableTestClass', $this->cacheableClass->getCachePrefix());
    }

    public function test_set_cache_ttl(): void
    {
        $this->cacheableClass->setCacheTTL(7200);

        $this->assertEquals(7200, $this->cacheableClass->getCacheTTL());
    }

    public function test_set_cache_tags(): void
    {
        $this->cacheableClass->setCacheTags(['tag1', 'tag2']);

        $this->assertEquals(['tag1', 'tag2'], $this->cacheableClass->getCacheTags());
    }

    public function test_remember_caches_callback_result(): void
    {
        $callCount = 0;

        $result1 = $this->cacheableClass->remember('test_key', function () use (&$callCount) {
            $callCount++;

            return 'test_value';
        });

        $result2 = $this->cacheableClass->remember('test_key', function () use (&$callCount) {
            $callCount++;

            return 'different_value';
        });

        // 回调应该只被调用一次
        $this->assertEquals(1, $callCount);
        $this->assertEquals('test_value', $result1);
        $this->assertEquals('test_value', $result2);
    }

    public function test_remember_with_custom_ttl(): void
    {
        $result = $this->cacheableClass->remember('custom_ttl_key', fn () => 'value', 60);

        $this->assertEquals('value', $result);
        $this->assertTrue($this->cacheableClass->hasCache('custom_ttl_key'));
    }

    public function test_put_and_get_cache(): void
    {
        $this->cacheableClass->putCache('put_key', 'put_value');

        $this->assertEquals('put_value', $this->cacheableClass->getCache('put_key'));
    }

    public function test_get_cache_returns_default_when_not_exists(): void
    {
        $result = $this->cacheableClass->getCache('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function test_forget_cache(): void
    {
        $this->cacheableClass->putCache('forget_key', 'value');
        $this->assertTrue($this->cacheableClass->hasCache('forget_key'));

        $this->cacheableClass->forgetCache('forget_key');
        $this->assertFalse($this->cacheableClass->hasCache('forget_key'));
    }

    public function test_forget_caches(): void
    {
        $this->cacheableClass->putCache('key1', 'value1');
        $this->cacheableClass->putCache('key2', 'value2');
        $this->cacheableClass->putCache('key3', 'value3');

        $this->cacheableClass->forgetCaches(['key1', 'key2']);

        $this->assertFalse($this->cacheableClass->hasCache('key1'));
        $this->assertFalse($this->cacheableClass->hasCache('key2'));
        $this->assertTrue($this->cacheableClass->hasCache('key3'));
    }

    public function test_has_cache(): void
    {
        $this->assertFalse($this->cacheableClass->hasCache('has_test_key'));

        $this->cacheableClass->putCache('has_test_key', 'value');

        $this->assertTrue($this->cacheableClass->hasCache('has_test_key'));
    }

    public function test_increment_cache(): void
    {
        $this->cacheableClass->putCache('counter', 10);

        $newValue = $this->cacheableClass->incrementCache('counter', 5);

        $this->assertEquals(15, $newValue);
    }

    public function test_decrement_cache(): void
    {
        $this->cacheableClass->putCache('counter', 10);

        $newValue = $this->cacheableClass->decrementCache('counter', 3);

        $this->assertEquals(7, $newValue);
    }

    public function test_remember_forever(): void
    {
        $result = $this->cacheableClass->rememberForever('forever_key', fn () => 'forever_value');

        $this->assertEquals('forever_value', $result);
        $this->assertTrue($this->cacheableClass->hasCache('forever_key'));
    }

    public function test_put_cache_forever(): void
    {
        $this->cacheableClass->putCacheForever('forever_put_key', 'forever_put_value');

        $this->assertEquals('forever_put_value', $this->cacheableClass->getCache('forever_put_key'));
    }

    public function test_make_cache_key_includes_prefix(): void
    {
        $this->cacheableClass->setCachePrefix('test');

        // 使用反射来测试 protected 方法
        $reflection = new \ReflectionClass($this->cacheableClass);
        $method = $reflection->getMethod('makeCacheKey');
        $method->setAccessible(true);

        $key = $method->invoke($this->cacheableClass, 'my_key');

        $this->assertStringContainsString('test', $key);
        $this->assertStringContainsString('my_key', $key);
    }

    public function test_supports_cache_tags_returns_false_for_array_driver(): void
    {
        // 使用反射来测试 protected 方法
        $reflection = new \ReflectionClass($this->cacheableClass);
        $method = $reflection->getMethod('supportsCacheTags');
        $method->setAccessible(true);

        // 默认测试环境使用 array 驱动，不支持标签
        $this->assertFalse($method->invoke($this->cacheableClass));
    }

    public function test_fluent_interface(): void
    {
        $result = $this->cacheableClass
            ->setCachePrefix('fluent')
            ->setCacheTTL(1800)
            ->setCacheTags(['fluent_tag']);

        $this->assertInstanceOf(CacheableTestClass::class, $result);
        $this->assertEquals('fluent', $result->getCachePrefix());
        $this->assertEquals(1800, $result->getCacheTTL());
        $this->assertEquals(['fluent_tag'], $result->getCacheTags());
    }

    public function test_cache_with_array_value(): void
    {
        $data = ['name' => 'test', 'value' => 123];

        $this->cacheableClass->putCache('array_key', $data);

        $result = $this->cacheableClass->getCache('array_key');

        $this->assertEquals($data, $result);
    }

    public function test_cache_with_object_value(): void
    {
        $object = new \stdClass();
        $object->name = 'test';
        $object->value = 123;

        $this->cacheableClass->putCache('object_key', $object);

        $result = $this->cacheableClass->getCache('object_key');

        $this->assertEquals($object->name, $result->name);
        $this->assertEquals($object->value, $result->value);
    }

    public function test_remember_with_null_value(): void
    {
        $callCount = 0;

        $result1 = $this->cacheableClass->remember('null_key', function () use (&$callCount) {
            $callCount++;

            return null;
        });

        // null 值不会被缓存，所以第二次调用会再次执行回调
        $result2 = $this->cacheableClass->remember('null_key', function () use (&$callCount) {
            $callCount++;

            return null;
        });

        $this->assertNull($result1);
        $this->assertNull($result2);
        // 由于 null 不被缓存，回调会被调用两次
        $this->assertEquals(2, $callCount);
    }
}

/**
 * 测试用的类.
 */
class CacheableTestClass
{
    use Cacheable;

    protected int $cacheTTL = 3600;

    protected ?string $cachePrefix = null;

    protected array $cacheTags = [];
}
