<?php

declare(strict_types=1);

namespace Modules\Mail\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Modules\Mail\Support\MailTrackingService;
use Symfony\Component\Mime\Email;

/**
 * 通用邮件追踪监听器
 * 自动为所有发送的邮件注入追踪功能.
 */
class UniversalMailTrackingListener
{
    public function __construct(
        protected MailTrackingService $trackingService
    ) {
    }

    /**
     * 邮件发送前：自动注入追踪代码
     */
    public function handle(MessageSending $event): void
    {
        try {
            $message = $event->message;

            // 获取邮件的HTML内容（无论来源）
            $htmlContent = $this->extractHtmlContent($message);

            if (!$htmlContent) {
                Log::debug('邮件不包含HTML内容，跳过追踪注入');

                return;
            }

            // 提取收件人信息
            $recipients = $this->extractRecipients($message);

            if (empty($recipients)) {
                Log::debug('邮件没有收件人，跳过追踪注入');

                return;
            }

            // 提取任务ID（从消息头或其他方式）
            $taskId = $this->extractTaskId($message);

            // 为第一个收件人处理追踪（如果有多个收件人，每个都会有单独的追踪记录）
            $primaryRecipient = $recipients[0];

            $trackingData = [
                'recipient' => $primaryRecipient,
                'task_id' => $taskId,
            ];

            // 处理内容并注入追踪
            $processedContent = $this->trackingService->processMailContent($htmlContent, $trackingData);

            // 更新邮件内容
            $this->updateMessageContent($message, $processedContent);

            Log::info('邮件追踪注入成功', [
                'recipient' => $primaryRecipient,
                'task_id' => $taskId,
                'content_length' => strlen($processedContent),
            ]);

        } catch (\Exception $e) {
            Log::error('邮件追踪注入失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 从任何类型的邮件消息中提取HTML内容.
     */
    private function extractHtmlContent($message): ?string
    {
        try {
            // 处理 Symfony Email 对象
            if ($message instanceof Email) {
                return $message->getHtmlBody();
            }

            // 处理 Swift Message 对象（Laravel 8及以下版本）
            if (method_exists($message, 'getBody')) {
                $body = $message->getBody();

                if (is_string($body)) {
                    return $body;
                }

                // 处理多部分消息
                if (method_exists($message, 'getChildren')) {
                    foreach ($message->getChildren() as $child) {
                        if ($child->getContentType() === 'text/html') {
                            return $child->getBody();
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('提取邮件HTML内容失败', [
                'error' => $e->getMessage(),
                'message_class' => get_class($message),
            ]);

            return null;
        }
    }

    /**
     * 提取邮件收件人.
     */
    private function extractRecipients($message): array
    {
        try {
            $recipients = [];

            // 处理 Symfony Email 对象
            if ($message instanceof Email) {
                $toAddresses = $message->getTo();
                foreach ($toAddresses as $address) {
                    $recipients[] = $address->getAddress();
                }

                return $recipients;
            }

            // 处理 Swift Message 对象
            if (method_exists($message, 'getTo')) {
                $toAddresses = $message->getTo();
                if (is_array($toAddresses)) {
                    return array_keys($toAddresses);
                }
            }

            return [];

        } catch (\Exception $e) {
            Log::warning('提取邮件收件人失败', [
                'error' => $e->getMessage(),
                'message_class' => get_class($message),
            ]);

            return [];
        }
    }

    /**
     * 从消息头中提取任务ID.
     */
    private function extractTaskId($message): int
    {
        try {
            // 处理 Symfony Email 对象
            if ($message instanceof Email) {
                $headers = $message->getHeaders();
                if ($headers->has('X-Task-ID')) {
                    return (int) $headers->get('X-Task-ID')->getBody();
                }
            }

            // 处理 Swift Message 对象
            if (method_exists($message, 'getHeaders')) {
                $headers = $message->getHeaders();
                if (method_exists($headers, 'get') && $headers->get('X-Task-ID')) {
                    return (int) $headers->get('X-Task-ID')->getFieldBody();
                }
            }

            return 0;

        } catch (\Exception $e) {
            Log::debug('提取任务ID失败', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 更新邮件内容.
     */
    private function updateMessageContent($message, string $processedContent): void
    {
        try {
            // 处理 Symfony Email 对象
            if ($message instanceof Email) {
                $message->html($processedContent);

                return;
            }

            // 处理 Swift Message 对象
            if (method_exists($message, 'setBody')) {
                $message->setBody($processedContent, 'text/html');

                return;
            }

            Log::warning('无法更新邮件内容，不支持的消息类型', [
                'message_class' => get_class($message),
            ]);

        } catch (\Exception $e) {
            Log::error('更新邮件内容失败', [
                'error' => $e->getMessage(),
                'message_class' => get_class($message),
            ]);
        }
    }
}
