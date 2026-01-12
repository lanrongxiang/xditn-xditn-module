<?php

declare(strict_types=1);

namespace Modules\Cms\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum UrlPattern: int implements Enum
{
    use EnumTrait;

    case DYNAMIC = 1; // 动态模式
    case STATIC = 2; // 静态
    case MONTH_DAY = 3; // 月日
    case YEAR_MON_DAY = 4; // 年月日

    /**
     * Get the pattern label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::DYNAMIC => '动态模式',
            self::STATIC => '静态模式',
            self::MONTH_DAY => '月日模式',
            self::YEAR_MON_DAY => '年月日模式',
        };
    }
}
