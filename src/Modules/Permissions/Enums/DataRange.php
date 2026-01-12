<?php

declare(strict_types=1);

namespace Modules\Permissions\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum DataRange: int implements Enum
{
    use EnumTrait;

    case All_Data = 1; // 全部数据
    case Personal_Choose = 2; // 自定义数据
    case Personal_Data = 3; // 本人数据
    case Department_Data = 4; // 部门数据
    case Department_DOWN_Data = 5; // 部门及以下数据

    /**
     * Get the data range label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::All_Data => '全部数据',
            self::Personal_Choose => '自定义数据',
            self::Personal_Data => '本人数据',
            self::Department_Data => '部门数据',
            self::Department_DOWN_Data => '部门及以下数据',
        };
    }
}
