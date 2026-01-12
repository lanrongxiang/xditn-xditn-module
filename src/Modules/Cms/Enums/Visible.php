<?php

declare(strict_types=1);

namespace Modules\Cms\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum Visible: int implements Enum
{
    use EnumTrait;

    case PUBLIC = 1; // 公开
    case SECRET = 2; // 私密
    case PASSWORD = 3; // 密码查看

    /**
     * Get the visible label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => '公开',
            self::SECRET => '私密',
            self::PASSWORD => '密码查看',
        };
    }
}
