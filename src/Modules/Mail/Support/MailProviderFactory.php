<?php

declare(strict_types=1);

namespace Modules\Mail\Support;

use Modules\Mail\Enums\MailProvider;
use Modules\Mail\Exceptions\MailException;
use Modules\Mail\Support\Providers\AliyunMailProvider;
use Modules\Mail\Support\Providers\SendCloudMailProvider;
use Modules\Mail\Support\Providers\TencentMailProvider;

/**
 * 邮件提供商工厂类.
 */
class MailProviderFactory
{
    /**
     * 创建邮件提供商实例.
     */
    public static function create(string|MailProvider $provider): AbstractMailProvider
    {
        $providerEnum = $provider instanceof MailProvider ? $provider : MailProvider::tryFrom($provider);

        if ($providerEnum === null) {
            throw new MailException("不支持的邮件提供商: {$provider}");
        }

        return match ($providerEnum) {
            MailProvider::ALIYUN => new AliyunMailProvider(),
            MailProvider::TENCENT => new TencentMailProvider(),
            MailProvider::SENDCLOUD => new SendCloudMailProvider(),
        };
    }
}
