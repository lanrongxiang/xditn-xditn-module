<?php

declare(strict_types=1);

namespace Modules\Mail\Support\Providers;

use Modules\Mail\Enums\MailProvider;
use Modules\Mail\Support\AbstractMailProvider;

/**
 * 阿里云邮件服务提供商.
 */
class AliyunMailProvider extends AbstractMailProvider
{
    /**
     * 加载配置.
     */
    protected function loadConfig(): array
    {
        return config('mails.direct_mail', []);
    }

    /**
     * 获取提供商名称.
     */
    public function getProviderName(): string
    {
        return MailProvider::ALIYUN->value();
    }
}
