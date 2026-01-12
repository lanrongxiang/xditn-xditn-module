<?php

declare(strict_types=1);

namespace Modules\Permissions\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum MenuStatus: int implements Enum
{
    use EnumTrait;

    case Show = 1; // 显示
    case Hidden = 2; // 隐藏

    /**
     * Get the status label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::Show => '显示',
            self::Hidden => '隐藏',
        };
    }
}
