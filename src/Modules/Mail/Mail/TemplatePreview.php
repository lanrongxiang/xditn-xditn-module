<?php

declare(strict_types=1);

namespace Modules\Mail\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 邮件模板预览 Mailable.
 */
class TemplatePreview extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $bladeContent,
        public array $data = [],
        public string $previewSubject = '邮件预览'
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->previewSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // 创建临时视图名称
        $tempViewName = 'mail-preview-'.uniqid();

        // 确保目录存在
        $viewPath = resource_path('views');
        if (!file_exists($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        // 写入临时Blade文件
        $tempFilePath = $viewPath.'/'.$tempViewName.'.blade.php';
        file_put_contents($tempFilePath, $this->bladeContent);

        // 注册清理回调
        register_shutdown_function(function () use ($tempFilePath) {
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        });

        return new Content(
            view: $tempViewName,
            with: $this->data,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
