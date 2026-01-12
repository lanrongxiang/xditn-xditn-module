<?php

declare(strict_types=1);

namespace Modules\Common\Enums;

enum TranslationMethod: string
{
    case AI = 'ai';
    case BAIDU = 'baidu';
    case GOOGLE = 'google';

    /**
     * 获取显示名称.
     */
    public function label(): string
    {
        return match ($this) {
            self::AI => 'AI翻译',
            self::BAIDU => '百度翻译',
            self::GOOGLE => 'Google翻译',
        };
    }
}
