<?php

declare(strict_types=1);

namespace Modules\Mail\Support\Providers;

use Modules\Mail\Enums\MailProvider;
use Modules\Mail\Support\AbstractMailProvider;

/**
 * 腾讯云邮件服务提供商.
 */
class TencentMailProvider extends AbstractMailProvider
{
    /**
     * 加载配置.
     */
    protected function loadConfig(): array
    {
        return config('mails.ses', []);
    }

    /**
     * 获取提供商名称.
     */
    public function getProviderName(): string
    {
        return MailProvider::TENCENT->value();
    }
}
