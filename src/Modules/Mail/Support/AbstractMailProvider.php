<?php

declare(strict_types=1);

namespace Modules\Mail\Support;

use Exception;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mail\Exceptions\MailException;

/**
 * 邮件服务提供商抽象类
 * 直接使用 Laravel SMTP 类注入配置.
 */
abstract class AbstractMailProvider
{
    protected array $config = [];
    protected string $mailerName;
    protected MailTrackingService $trackingService;

    public function __construct()
    {
        $this->config = $this->loadConfig();

        if (empty($this->config)) {
            throw new MailException('邮件配置为空');
        }

        $this->mailerName = $this->getProviderName().'_mailer';
        $this->trackingService = app(MailTrackingService::class);
        $this->configureMailer();
    }

    /**
     * 配置 Laravel Mail - 创建专用的 mailer 配置.
     */
    protected function configureMailer(): void
    {
        try {
            // 为当前提供商创建专用的 mailer 配置
            Config::set([
                "mail.mailers.{$this->mailerName}" => [
                    'transport' => 'smtp',
                    'host' => $this->config['host'],
                    'port' => (int) $this->config['port'],
                    'encryption' => $this->config['encryption'],
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'timeout' => 60,
                ],
            ]);
        } catch (Exception $e) {
            throw new MailException('邮件配置失败: '.$e->getMessage());
        }
    }

    /**
     * 发送邮件 - 支持HTML和Blade模板，自动追踪.
     */
    public function send(string $to, string $subject, string $content, array $options = []): bool
    {
        try {
            // 如果有task_id，直接在这里处理追踪
            $processedContent = $content;
            if (isset($options['task_id'])) {
                $trackingData = [
                    'task_id' => $options['task_id'],
                    'recipient' => $to,
                ];

                // 处理追踪功能
                $processedContent = $this->trackingService->processMailContent($content, $trackingData);
            }

            // 使用专用的 mailer 发送邮件
            Mail::mailer($this->mailerName)->send([], [], function (Message $message) use ($to, $subject, $processedContent, $options) {
                $message->to($to)
                    ->subject($subject)
                    ->from($this->config['from_address'], $this->config['from_name'] ?? '');

                // 设置任务ID到消息头（用于追踪关联）
                if (isset($options['task_id'])) {
                    // Laravel 12 使用不同的方法设置消息头
                    if (method_exists($message, 'withSymfonyMessage')) {
                        $message->withSymfonyMessage(function ($message) use ($options) {
                            $message->getHeaders()->addTextHeader('X-Task-ID', (string) $options['task_id']);
                        });
                    } elseif (method_exists($message, 'getSwiftMessage')) {
                        $message->getSwiftMessage()->getHeaders()->addTextHeader('X-Task-ID', (string) $options['task_id']);
                    }
                    // 如果都不支持，我们可以通过其他方式传递task_id
                }

                // 支持不同内容类型
                if ($this->isBladeTemplate($processedContent)) {
                    // Blade 模板
                    $renderedContent = view($processedContent, $options['data'] ?? [])->render();
                    $message->setBody($renderedContent, 'text/html');
                } else {
                    // 直接HTML内容（已经处理过追踪）
                    $message->html($processedContent);
                }

                // 处理抄送
                if (!empty($options['cc'])) {
                    $message->cc($options['cc']);
                }

                // 处理密送
                if (!empty($options['bcc'])) {
                    $message->bcc($options['bcc']);
                }

                // 处理附件
                if (!empty($options['attachments'])) {
                    foreach ($options['attachments'] as $attachment) {
                        $message->attach($attachment);
                    }
                }
            });

            return true;
        } catch (Exception $e) {
            Log::error($this->getProviderName().' 邮件发送失败', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            throw new MailException('邮件发送失败: '.$e->getMessage());
        }
    }

    /**
     * 检测是否为Blade模板名称.
     */
    private function isBladeTemplate(string $content): bool
    {
        // 检测是否为Blade模板名称
        return !str_contains($content, '<') && view()->exists($content);
    }

    /**
     * 批量发送邮件.
     */
    public function sendBatch(array $recipients, string $subject, string $content, array $options = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = [
                'email' => $recipient,
                'is_success' => false,
                'message' => '',
            ];

            try {
                if ($this->send($recipient, $subject, $content, $options)) {
                    $results[$recipient]['is_success'] = true;
                }
            } catch (MailException $e) {
                $results[$recipient]['message'] = $e->getMessage();
            }
        }

        return array_values($results);
    }

    /**
     * 获取配置信息.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 加载配置 - 子类实现.
     */
    abstract protected function loadConfig(): array;

    /**
     * 获取提供商名称 - 子类实现.
     */
    abstract public function getProviderName(): string;
}
