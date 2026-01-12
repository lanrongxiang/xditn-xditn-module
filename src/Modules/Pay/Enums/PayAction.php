<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

/**
 * 支付动作枚举.
 */
enum PayAction: string implements Enum
{
    use EnumTrait;

    case APP = 'app';
    case H5 = 'h5';
    case MINI = 'mini';
    case POS = 'pos';
    case SCAN = 'scan';
    case WEB = 'web'; // 支付宝
    case MP = 'mp'; // 微信公众号支付

    /**
     * Get the action label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::APP => 'APP',
            self::H5 => 'H5',
            self::MINI => '小程序',
            self::WEB => 'WEB网页',
            self::POS => 'POS机',
            self::SCAN => '扫码',
            self::MP => '微信公众号',
        };
    }
}
