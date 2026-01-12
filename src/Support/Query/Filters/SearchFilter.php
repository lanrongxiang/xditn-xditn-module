<?php

declare(strict_types=1);

namespace XditnModule\Support\Query\Filters;

use Illuminate\Database\Eloquent\Builder;
use XditnModule\Support\Query\Filter;

/**
 * 搜索过滤器.
 *
 * 支持在多个字段中进行模糊搜索。
 *
 * 使用示例：
 * ```php
 * // 配置搜索字段
 * protected array $filters = [
 *     'keyword' => new SearchFilter(['name', 'email', 'phone']),
 * ];
 *
 * // 查询
 * User::filter(['keyword' => '张三'])->get();
 * ```
 */
class SearchFilter extends Filter
{
    /**
     * @param array<int, string> $columns 要搜索的字段列表
     */
    public function __construct(
        protected array $columns = []
    ) {
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        if (empty($this->columns)) {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($value) {
            foreach ($this->columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}($column, 'like', '%'.$value.'%');
            }
        });
    }
}
