<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum RefundStatus: int implements Enum
{
    use EnumTrait;

    case NONE = 0;
    case PENDING = 1;
    case SUCCESS = 2;
    case FAILED = 3;
    case REFUSE = 4;

    /**
     * Get the status label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::NONE => '未退款',
            self::PENDING => '待退款',
            self::SUCCESS => '退款成功',
            self::FAILED => '退款失败',
            self::REFUSE => '拒绝退款',
        };
    }
}
