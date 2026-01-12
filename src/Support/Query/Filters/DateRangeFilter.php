<?php

declare(strict_types=1);

namespace XditnModule\Support\Query\Filters;

use Illuminate\Database\Eloquent\Builder;
use XditnModule\Support\Query\Filter;

/**
 * 日期范围过滤器.
 *
 * 用于按日期范围过滤查询结果。
 *
 * 使用示例：
 * ```php
 * // 传入数组 [开始日期, 结束日期]
 * Order::filter(['date_range' => ['2024-01-01', '2024-12-31']])->get();
 *
 * // 或者传入带键的数组
 * Order::filter(['date_range' => ['start' => '2024-01-01', 'end' => '2024-12-31']])->get();
 * ```
 */
class DateRangeFilter extends Filter
{
    public function __construct(
        protected string $column = 'created_at'
    ) {
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        if (!is_array($value)) {
            return $builder;
        }

        // 支持 [start, end] 或 ['start' => x, 'end' => y] 格式
        $start = $value['start'] ?? $value[0] ?? null;
        $end = $value['end'] ?? $value[1] ?? null;

        if ($start && $end) {
            return $builder->whereBetween($this->column, [$start, $end]);
        }

        if ($start) {
            return $builder->where($this->column, '>=', $start);
        }

        if ($end) {
            return $builder->where($this->column, '<=', $end);
        }

        return $builder;
    }

    public function isValid(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $start = $value['start'] ?? $value[0] ?? null;
        $end = $value['end'] ?? $value[1] ?? null;

        return $start !== null || $end !== null;
    }
}
