<?php

declare(strict_types=1);

namespace XditnModule\Support\Query\Filters;

use Illuminate\Database\Eloquent\Builder;
use XditnModule\Support\Query\Filter;

/**
 * IN 过滤器.
 *
 * 用于 WHERE IN 查询。
 *
 * 使用示例：
 * ```php
 * // 配置
 * protected array $filters = [
 *     'status' => new InFilter('status'),
 *     'user_ids' => new InFilter('user_id'),
 * ];
 *
 * // 查询 - 传入数组
 * Order::filter(['status' => [1, 2, 3]])->get();
 *
 * // 查询 - 传入逗号分隔字符串
 * Order::filter(['user_ids' => '1,2,3'])->get();
 * ```
 */
class InFilter extends Filter
{
    public function __construct(
        protected string $column = 'id'
    ) {
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        $values = $this->transformValue($value);

        if (empty($values)) {
            return $builder;
        }

        return $builder->whereIn($this->column, $values);
    }

    public function transformValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_filter($value, fn ($v) => $v !== null && $v !== '');
        }

        if (is_string($value)) {
            return array_filter(explode(',', $value), fn ($v) => $v !== '');
        }

        return [$value];
    }

    public function isValid(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }

        return $value !== null && $value !== '';
    }
}
