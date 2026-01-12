<?php

declare(strict_types=1);

namespace Modules\Mail\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum MailProvider: string implements Enum
{
    use EnumTrait;

    case ALIYUN = 'direct_mail'; // 阿里云邮件
    case TENCENT = 'ses'; // 腾讯云邮件
    case SENDCLOUD = 'send_cloud'; // SendCloud邮件

    /**
     * Get the provider label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::ALIYUN => '阿里云邮件',
            self::TENCENT => '腾讯云邮件',
            self::SENDCLOUD => 'SendCloud邮件',
        };
    }
}
