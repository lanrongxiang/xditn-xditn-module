<?php

declare(strict_types=1);

namespace Modules\Common\Support\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * 金额转换 Cast.
 *
 * 数据库存储：分（整数）
 * 接口返回：元（浮点数）
 *
 * 使用方式：
 * ```php
 * protected $casts = [
 *     'amount' => AmountCast::class,
 *     'price' => AmountCast::class,
 * ];
 * ```
 */
class AmountCast implements CastsAttributes
{
    /**
     * 将存储的值转换为属性值（分转元）.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return float|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return $value / 100;
    }

    /**
     * 将属性值转换为存储值（元转分）.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return int|null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // 传入的值统一当作元，转换为分存储
        return (int) round((float) $value * 100);
    }
}
