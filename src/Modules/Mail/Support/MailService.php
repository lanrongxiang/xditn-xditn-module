<?php

declare(strict_types=1);

namespace Modules\Mail\Support;

use Modules\Mail\Enums\MailProvider;

/**
 * 邮件服务类 - 统一入口.
 */
class MailService
{
    protected AbstractMailProvider $provider;

    public function __construct(string|MailProvider $providerName = null)
    {
        $provider = $providerName ?? MailProvider::TENCENT;
        $this->provider = MailProviderFactory::create($provider);
    }

    /**
     * 创建指定提供商的邮件服务实例.
     */
    public static function provider(string|MailProvider $providerName): static
    {
        return new static($providerName);
    }

    /**
     * 发送单封邮件.
     */
    public function send(string $to, string $subject, string $content, array $options = []): bool
    {
        return $this->provider->send($to, $subject, $content, $options);
    }

    /**
     * 批量发送邮件.
     */
    public function sendBatch(array $recipients, string $subject, string $content, array $options = []): array
    {
        return $this->provider->sendBatch($recipients, $subject, $content, $options);
    }

    /**
     * 获取配置信息.
     */
    public function getConfig(): array
    {
        return $this->provider->getConfig();
    }
}
