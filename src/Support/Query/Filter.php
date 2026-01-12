<?php

declare(strict_types=1);

namespace XditnModule\Support\Query;

use Illuminate\Database\Eloquent\Builder;

/**
 * 查询过滤器抽象类.
 *
 * 用于封装复杂的查询逻辑，使查询条件可复用、可测试。
 *
 * 使用示例：
 * ```php
 * // 创建过滤器
 * class StatusFilter extends Filter
 * {
 *     public function apply(Builder $builder, mixed $value): Builder
 *     {
 *         return $builder->where('status', $value);
 *     }
 * }
 *
 * // 创建日期范围过滤器
 * class DateRangeFilter extends Filter
 * {
 *     public function apply(Builder $builder, mixed $value): Builder
 *     {
 *         if (is_array($value) && count($value) === 2) {
 *             return $builder->whereBetween('created_at', $value);
 *         }
 *         return $builder;
 *     }
 * }
 *
 * // 在模型中使用
 * class Order extends Model
 * {
 *     use HasFilters;
 *
 *     protected array $filters = [
 *         'status' => StatusFilter::class,
 *         'date_range' => DateRangeFilter::class,
 *     ];
 * }
 *
 * // 查询时使用
 * Order::filter(['status' => 1, 'date_range' => ['2024-01-01', '2024-12-31']])->get();
 * ```
 */
abstract class Filter
{
    /**
     * 应用过滤条件到查询构建器.
     *
     * @param Builder $builder 查询构建器
     * @param mixed $value 过滤值
     *
     * @return Builder 返回修改后的查询构建器
     */
    abstract public function apply(Builder $builder, mixed $value): Builder;

    /**
     * 验证过滤值是否有效.
     *
     * 子类可以重写此方法来添加自定义验证逻辑。
     *
     * @param mixed $value 过滤值
     */
    public function isValid(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * 在应用过滤之前转换值.
     *
     * 子类可以重写此方法来转换输入值。
     *
     * @param mixed $value 原始值
     *
     * @return mixed 转换后的值
     */
    public function transformValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * 安全地应用过滤条件.
     *
     * 此方法会先验证值，然后转换值，最后应用过滤。
     *
     * @param Builder $builder 查询构建器
     * @param mixed $value 过滤值
     */
    public function safeApply(Builder $builder, mixed $value): Builder
    {
        if (!$this->isValid($value)) {
            return $builder;
        }

        $transformedValue = $this->transformValue($value);

        return $this->apply($builder, $transformedValue);
    }
}
