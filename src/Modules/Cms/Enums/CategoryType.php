<?php

declare(strict_types=1);

namespace Modules\Cms\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum CategoryType: int implements Enum
{
    use EnumTrait;

    case ARTICLE = 1; // 文章
    case HREF = 2; // 链接

    /**
     * Get the category type label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::ARTICLE => '文章类型',
            self::HREF => '链接类型',
        };
    }
}
