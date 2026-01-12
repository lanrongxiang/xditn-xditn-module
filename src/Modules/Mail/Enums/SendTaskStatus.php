<?php

declare(strict_types=1);

namespace Modules\Mail\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum SendTaskStatus: int implements Enum
{
    use EnumTrait;

    case PENDING = 0;     // 未开始
    case PROCESSING = 1;  // 进行中
    case COMPLETED = 2;   // 已完成
    case FAILED = 3;      // 失败

    /**
     * Get the status label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => '未开始',
            self::PROCESSING => '进行中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
        };
    }
}
