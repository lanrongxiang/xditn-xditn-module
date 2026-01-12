<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

trait ScopeTrait
{
    /**
     * creator.
     */
    public static function scopeCreator($query): void
    {
        $model = app(static::class);

        if (in_array($model->getCreatorIdColumn(), $model->getFillable())) {
            $userModel = app(getAuthUserModel());

            $query->addSelect([
                'creator' => $userModel->whereColumn($userModel->getKeyName(), $model->getTable().'.'.$model->getCreatorIdColumn())
                    ->select('username')->limit(1),
            ]);
        }
    }
}
