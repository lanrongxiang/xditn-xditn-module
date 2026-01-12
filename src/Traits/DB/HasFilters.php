<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

use Illuminate\Database\Eloquent\Builder;
use XditnModule\Support\Query\Filter;

/**
 * 查询过滤器 Trait.
 *
 * 为模型添加过滤器支持，使复杂查询更加清晰和可维护。
 *
 * 使用示例：
 * ```php
 * use XditnModule\Traits\DB\HasFilters;
 * use XditnModule\Support\Query\Filters\StatusFilter;
 * use XditnModule\Support\Query\Filters\DateRangeFilter;
 * use XditnModule\Support\Query\Filters\SearchFilter;
 *
 * class Order extends Model
 * {
 *     use HasFilters;
 *
 *     // 方式一：使用过滤器类名（自动实例化）
 *     protected array $filters = [
 *         'status' => StatusFilter::class,
 *         'date_range' => DateRangeFilter::class,
 *     ];
 *
 *     // 方式二：在 boot 方法中配置（支持构造参数）
 *     protected static function booted()
 *     {
 *         static::addFilter('keyword', new SearchFilter(['name', 'phone', 'email']));
 *         static::addFilter('created_at', new DateRangeFilter('created_at'));
 *     }
 * }
 *
 * // 使用过滤器
 * Order::filter([
 *     'status' => 1,
 *     'keyword' => '张三',
 *     'date_range' => ['2024-01-01', '2024-12-31'],
 * ])->get();
 * ```
 */
trait HasFilters
{
    /**
     * 过滤器配置.
     *
     * 键为请求参数名，值为 Filter 类名或实例。
     *
     * @var array<string, class-string<Filter>|Filter>
     */
    protected array $filters = [];

    /**
     * 全局过滤器（所有模型共享）.
     *
     * @var array<string, array<string, Filter>>
     */
    protected static array $globalFilters = [];

    /**
     * 添加全局过滤器.
     */
    public static function addFilter(string $name, Filter $filter): void
    {
        static::$globalFilters[static::class][$name] = $filter;
    }

    /**
     * 移除全局过滤器.
     */
    public static function removeFilter(string $name): void
    {
        unset(static::$globalFilters[static::class][$name]);
    }

    /**
     * 获取所有过滤器.
     *
     * @return array<string, Filter>
     */
    public function getFilters(): array
    {
        $filters = [];

        // 合并属性定义的过滤器
        foreach ($this->filters as $name => $filter) {
            $filters[$name] = $this->resolveFilter($filter);
        }

        // 合并全局过滤器
        if (isset(static::$globalFilters[static::class])) {
            foreach (static::$globalFilters[static::class] as $name => $filter) {
                $filters[$name] = $filter;
            }
        }

        return $filters;
    }

    /**
     * 解析过滤器.
     *
     * @param class-string<Filter>|Filter $filter
     */
    protected function resolveFilter(string|Filter $filter): Filter
    {
        if ($filter instanceof Filter) {
            return $filter;
        }

        return new $filter();
    }

    /**
     * 应用过滤器到查询.
     *
     * @param Builder $query 查询构建器
     * @param array<string, mixed> $filterValues 过滤条件
     */
    public function scopeFilter(Builder $query, array $filterValues): Builder
    {
        $filters = $this->getFilters();

        foreach ($filterValues as $name => $value) {
            if (!isset($filters[$name])) {
                continue;
            }

            $filter = $filters[$name];

            // 使用安全应用方法（包含验证和转换）
            $filter->safeApply($query, $value);
        }

        return $query;
    }

    /**
     * 从请求中自动获取过滤参数并应用.
     *
     * @param Builder $query 查询构建器
     */
    public function scopeFilterFromRequest(Builder $query): Builder
    {
        $filters = $this->getFilters();
        $filterValues = [];

        foreach (array_keys($filters) as $name) {
            $value = request()->input($name);
            if ($value !== null) {
                $filterValues[$name] = $value;
            }
        }

        return $this->scopeFilter($query, $filterValues);
    }

    /**
     * 设置过滤器.
     *
     * @param array<string, class-string<Filter>|Filter> $filters
     *
     * @return $this
     */
    public function setFilters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * 添加单个过滤器到实例.
     *
     * @return $this
     */
    public function addInstanceFilter(string $name, Filter $filter): static
    {
        $this->filters[$name] = $filter;

        return $this;
    }
}
