<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

/**
 * 订单类型枚举.
 */
enum OrderType: string implements Enum
{
    use EnumTrait;

    case RECHARGE = 'recharge'; // 金币充值订单
    case VIDEO_SUBSCRIPTION = 'video_subscription'; // 视频订阅
    case VIDEO_PURCHASE = 'video_purchase'; // 视频单次购买
    case VIDEO_RENEWAL = 'video_renewal'; // 视频续费
    case GENERAL = 'general'; // 普通订单

    /**
     * Get the order type label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::RECHARGE => '金币充值',
            self::VIDEO_SUBSCRIPTION => '视频订阅',
            self::VIDEO_PURCHASE => '视频购买',
            self::VIDEO_RENEWAL => '视频续费',
            self::GENERAL => '普通订单',
        };
    }
}
