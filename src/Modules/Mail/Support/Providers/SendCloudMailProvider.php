<?php

declare(strict_types=1);

namespace Modules\Mail\Support\Providers;

use Modules\Mail\Enums\MailProvider;
use Modules\Mail\Support\AbstractMailProvider;

/**
 * SendCloud 邮件服务提供商.
 */
class SendCloudMailProvider extends AbstractMailProvider
{
    /**
     * 加载配置.
     */
    protected function loadConfig(): array
    {
        return config('mails.send_cloud', []);
    }

    /**
     * 获取提供商名称.
     */
    public function getProviderName(): string
    {
        return MailProvider::SENDCLOUD->value();
    }
}
