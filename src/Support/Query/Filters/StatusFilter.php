<?php

declare(strict_types=1);

namespace XditnModule\Support\Query\Filters;

use Illuminate\Database\Eloquent\Builder;
use XditnModule\Support\Query\Filter;

/**
 * 状态过滤器.
 *
 * 用于按状态字段过滤查询结果。
 */
class StatusFilter extends Filter
{
    public function __construct(
        protected string $column = 'status'
    ) {
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        return $builder->where($this->column, $value);
    }

    public function isValid(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
