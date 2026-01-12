<?php

declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use XditnModule\Exceptions\FailedException;

class Area extends Model
{
    protected $table = 'areas';

    public function getAll()
    {
        if (!Schema::hasTable('areas')) {
            throw new FailedException('请使用 php artisan xditn:module:areas 获取地区数据源');
        }

        return $this->whereIn('level', [1, 2])->get(['id', 'parent_id', 'name'])->toTree(0, 'parent_id')
            ->filter(function ($area) {
                return isset($area['children']);
            })->values();
    }
}
