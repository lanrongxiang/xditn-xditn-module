<?php

// +----------------------------------------------------------------------
// | XditnModule [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://XditnModule.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/xditn-module/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace XditnModule\Enums;

enum Status: int implements Enum
{
    use EnumTrait;

    case Enable = 1;

    case Disable = 2;

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Enable => '启用',
            self::Disable => '禁用',
        };
    }
}
