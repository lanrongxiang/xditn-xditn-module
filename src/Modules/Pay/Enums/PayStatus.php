<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum PayStatus: int implements Enum
{
    use EnumTrait;

    case PENDING = 1;
    case SUCCESS = 2;
    case FAILED = 3;
    case TIMEOUT = 4;

    /**
     * Get the status label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待支付',
            self::SUCCESS => '支付成功',
            self::FAILED => '支付失败',
            self::TIMEOUT => '超时未支付',
        };
    }
}
