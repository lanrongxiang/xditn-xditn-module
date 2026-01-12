<?php

declare(strict_types=1);

namespace Modules\Cms\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum ResourceType: int implements Enum
{
    use EnumTrait;

    case CAROUSEL = 1; // 轮播
    case FRIEND_LINK = 2; // 友情链接
    case AD = 3; // 广告

    /**
     * Get the resource type label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::CAROUSEL => '轮播图',
            self::FRIEND_LINK => '友情链接',
            self::AD => '广告',
        };
    }
}
