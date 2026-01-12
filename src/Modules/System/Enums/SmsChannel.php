<?php

declare(strict_types=1);

namespace Modules\System\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum SmsChannel: string implements Enum
{
    use EnumTrait;

    case ALIYUN = 'aliyun'; // 阿里云
    case QCLOUD = 'qcloud'; // 腾讯云

    /**
     * Get the channel label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::ALIYUN => '阿里云',
            self::QCLOUD => '腾讯云',
        };
    }
}
