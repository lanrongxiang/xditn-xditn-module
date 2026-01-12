<?php

namespace Modules\Mail\Support;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Modules\Mail\Enums\SendTaskStatus;
use Modules\Mail\Models\MailTemplate;
use Modules\Mail\Models\MailTrackingLog;
use Modules\Mail\Models\SendTask;

class SendMailTaskService
{
    protected MailService $mailService;
    protected MailTrackingService $trackingService;

    public function __construct(MailService $mailService, MailTrackingService $trackingService)
    {
        $this->mailService = $mailService;
        $this->trackingService = $trackingService;
    }

    /**
     * 获取待处理的任务
     */
    public function getPendingTasks(int $limit = 10)
    {
        return SendTask::where('status', SendTaskStatus::PENDING)
            ->orderBy('created_at')
            ->where('send_at', 0) // 立即发送任务
            ->orWhere('send_at', '<=', time()) // 定时发送的
            ->limit($limit)
            ->get();
    }

    /**
     * 批量处理发送任务
     */
    public function processTasks(int $limit = 10): array
    {
        $tasks = $this->getPendingTasks($limit);

        $processedCount = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($tasks as $task) {
            try {
                $result = $this->processSendTask($task);
                $processedCount++;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }

            } catch (Exception $e) {
                $failedCount++;
                $processedCount++;

                // 标记任务为失败
                $task->update([
                    'status' => SendTaskStatus::FAILED,
                    'finished_at' => time(),
                ]);

                Log::error('邮件发送任务处理失败', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = $processedCount > 0 ? "成功处理 {$processedCount} 个邮件任务" : '没有待处理的邮件任务';

        return [
            'processed' => $processedCount,
            'success' => $successCount,
            'failed' => $failedCount,
            'message' => $message,
        ];
    }

    /**
     * @param SendTask $task
     *
     * @return array
     *
     * @throws Exception
     */
    public function processSendTask(SendTask $task): array
    {
        // 更新任务状态为处理中
        $task->update(['status' => SendTaskStatus::PROCESSING]);

        // 获取邮件模板
        $template = MailTemplate::find($task->template_id);
        if (!$template || $template->isDisabled()) {
            throw new Exception('邮件模板不存在或已禁用');
        }

        // 解析收件人列表
        $recipients = $this->parseRecipients($task->recipients);
        if (empty($recipients)) {
            throw new Exception('收件人列表为空');
        }

        $successCount = 0;
        $failureCount = 0;

        // 批量发送邮件
        foreach ($recipients as $recipient) {
            try {
                // 渲染邮件内容
                $content = $this->renderMailContent($template, $recipient, $task);

                // 如果任务启用追踪，处理追踪元素
                if ($task->isTracking()) {
                    $trackingData = [
                        'task_id' => $task->id,
                        'recipient' => $recipient['email'],
                    ];
                    $content = $this->trackingService->processMailContent($content, $trackingData);
                }

                // 发送邮件
                $result = $this->mailService->send(
                    $recipient['email'],
                    $task->subject,
                    $content,
                    [
                        'from' => $task->from_address,
                        'html' => $template->isHtml() || $template->isBlade(),
                    ]
                );

                if ($result) {
                    $successCount++;
                    $this->logSendResult($task->id, $recipient['email'], true);
                } else {
                    $failureCount++;
                    $this->logSendResult($task->id, $recipient['email'], false, '发送失败');
                }

            } catch (Exception $e) {
                $failureCount++;
                $this->logSendResult($task->id, $recipient['email'], false, $e->getMessage());

                Log::error('邮件发送失败', [
                    'task_id' => $task->id,
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 更新任务统计信息
        $task->update([
            'success_num' => $successCount,
            'failure_num' => $failureCount,
            'status' => SendTaskStatus::COMPLETED,
            'finished_at' => time(),
        ]);

        Log::info('邮件发送任务完成', [
            'task_id' => $task->id,
            'success' => $successCount,
            'failure' => $failureCount,
        ]);

        return [
            'success' => $successCount > 0,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_recipients' => count($recipients),
        ];
    }

    /**
     * 解析收件人列表.
     */
    public function parseRecipients(string $recipients): array
    {
        $recipientList = [];
        $lines = explode("\n", $recipients);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // 支持格式：email 或 name <email>
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $line, $matches)) {
                $recipientList[] = [
                    'name' => trim($matches[1]),
                    'email' => trim($matches[2]),
                ];
            } elseif (filter_var($line, FILTER_VALIDATE_EMAIL)) {
                $recipientList[] = [
                    'name' => '',
                    'email' => $line,
                ];
            }
        }

        return $recipientList;
    }

    /**
     * 渲染邮件内容.
     */
    public function renderMailContent(MailTemplate $template, array $recipient, SendTask $task): string
    {
        $content = $template->content;

        if ($template->isBlade()) {
            // 渲染 Blade 模板
            try {
                $content = Blade::render(
                    $content,
                    [
                        'recipient' => $recipient,
                        'task' => $task,
                    ],
                    deleteCachedView: true
                );
            } catch (Exception $e) {
                Log::warning('Blade模板渲染失败，使用原始内容', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 替换基本变量
        $replacements = [
            '{{recipient_name}}' => $recipient['name'] ?: $recipient['email'],
            '{{recipient_email}}' => $recipient['email'],
            '{{task_subject}}' => $task->subject,
        ];

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * 记录发送结果日志.
     */
    public function logSendResult(int $taskId, string $email, bool $success, string $error = ''): void
    {
        MailTrackingLog::create([
            'task_id' => $taskId,
            'recipient' => $email,
            'is_delivered' => $success ? 1 : 0,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        if (!$success && $error) {
            Log::warning('邮件发送失败', [
                'task_id' => $taskId,
                'recipient' => $email,
                'error' => $error,
            ]);
        }
    }
}
