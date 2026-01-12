<?php

// +----------------------------------------------------------------------
// | XditnModule [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2022 https://XditnModule.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/XditnModule-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace XditnModule\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use XditnModule\Support\DB\SoftDelete;
use XditnModule\Traits\DB\BaseOperate;
use XditnModule\Traits\DB\DateformatTrait;
use XditnModule\Traits\DB\ScopeTrait;
use XditnModule\Traits\DB\Trans;
use XditnModule\Traits\DB\WithAttributes;

/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
abstract class XditnModuleModel extends Model
{
    use BaseOperate;
    use DateformatTrait;
    use ScopeTrait;
    use SoftDeletes;
    use Trans;
    use WithAttributes;

    /**
     * Unix timestamp format.
     */
    protected $dateFormat = 'U';

    /**
     * Pagination limit.
     */
    protected $perPage = 10;

    /**
     * Disable automatic timestamps.
     */
    public $timestamps = false;

    /**
     * Default casts.
     */
    protected array $defaultCasts = [];

    /**
     * Default hidden attributes.
     */
    protected array $defaultHidden = ['deleted_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->init();
    }

    /**
     * init.
     */
    protected function init(): void
    {
        $this->makeHidden($this->defaultHidden);

        $this->mergeCasts(array_merge($this->defaultCasts, $this->dateFormatCasts()));

        // auto use data range
        foreach (class_uses_recursive(static::class) as $trait) {
            if (str_contains($trait, 'DataRange')) {
                $this->setDataRange();
            }

            if (str_contains($trait, 'ColumnAccess')) {
                $this->setColumnAccess();
            }

            // Initialize AmountTrait if used
            if (str_contains($trait, 'AmountTrait') && method_exists($this, 'initializeAmountTrait')) {
                $this->initializeAmountTrait();
            }
        }
    }

    /**
     * soft delete.
     */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope(new SoftDelete());
    }

    /**
     * 覆盖 restore 方法.
     *
     * 修改 deleted_at 默认值
     */
    public function restore(): bool
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = 0;

        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }
}
