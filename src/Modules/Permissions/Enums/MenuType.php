<?php

declare(strict_types=1);

namespace Modules\Permissions\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum MenuType: int implements Enum
{
    use EnumTrait;

    case Top = 1; // 目录
    case Menu = 2; // 菜单
    case Action = 3; // 按钮

    /**
     * Get the menu type label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::Top => '目录类型',
            self::Menu => '菜单类型',
            self::Action => '按钮类型',
        };
    }
}
