<?php

declare(strict_types=1);

namespace Modules\Mail\Support;

use Illuminate\Support\Facades\URL;
use Modules\Mail\Models\MailTrackingLog;

/**
 * 邮件追踪服务
 *
 * 提供邮件打开追踪、链接点击追踪等功能
 */
class MailTrackingService
{
    /**
     * 处理邮件内容，注入追踪元素.
     *
     * @param string $htmlContent
     * @param array $trackingData
     *
     * @return string
     */
    public function processMailContent(string $htmlContent, array $trackingData): string
    {
        $trackingLog = $this->createTrackingLog($trackingData);

        return $this->injectTrackingElements($htmlContent, $trackingLog->id);
    }

    /**
     * 创建追踪记录.
     *
     * @param array $trackingData
     *
     * @return MailTrackingLog
     */
    private function createTrackingLog(array $trackingData): MailTrackingLog
    {
        return MailTrackingLog::create([
            'task_id' => $trackingData['task_id'],
            'recipient' => $trackingData['recipient'],
            'is_delivered' => false,
            'is_opened' => false,
            'is_clicked' => false,
            'is_bounced' => false,
        ]);
    }

    /**
     * 注入追踪元素到邮件内容中.
     *
     * @param string $content
     * @param int $trackingId
     *
     * @return string
     */
    private function injectTrackingElements(string $content, int $trackingId): string
    {
        if ($this->isHtmlContent($content)) {
            return $this->injectHtmlTracking($content, $trackingId);
        }

        return $this->injectTextTracking($content, $trackingId);
    }

    /**
     * 向HTML内容注入追踪元素.
     *
     * @param string $content
     * @param int $trackingId
     *
     * @return string
     */
    private function injectHtmlTracking(string $content, int $trackingId): string
    {
        // 重写链接为追踪链接
        $content = $this->rewriteLinksForTracking($content, $trackingId);

        // 注入追踪像素
        $trackingPixel = $this->generateTrackingPixel($trackingId);

        // 在</body>标签前插入追踪像素
        if (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $trackingPixel.'</body>', $content);
        } else {
            // 如果没有</body>标签，直接追加到末尾
            $content .= $trackingPixel;
        }

        return $content;
    }

    /**
     * 重写链接为追踪链接.
     *
     * @param string $content
     * @param int $trackingId
     *
     * @return string
     */
    private function rewriteLinksForTracking(string $content, int $trackingId): string
    {
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
            function ($matches) use ($trackingId) {
                $beforeHref = $matches[1];
                $originalUrl = $matches[2];
                $afterHref = $matches[3];

                // 跳过追踪链接和特殊链接
                if (str_contains($originalUrl, 'mail/track') ||
                    str_starts_with($originalUrl, 'mailto:') ||
                    str_starts_with($originalUrl, '#')) {
                    return $matches[0];
                }

                $trackingUrl = $this->generateTrackingLink($originalUrl, $trackingId);

                return "<a {$beforeHref}href=\"{$trackingUrl}\"{$afterHref}>";
            },
            $content
        );
    }

    /**
     * 生成追踪像素.
     *
     * @param int $trackingId
     *
     * @return string
     */
    public function generateTrackingPixel(int $trackingId): string
    {
        $trackingUrl = URL::route('mail.track.open', ['tracking_id' => $trackingId]);
        $token = $this->generateTrackingToken($trackingId);

        return sprintf(
            '<img src="%s?token=%s" alt="" width="1" height="1" style="display:none;">',
            $trackingUrl,
            $token
        );
    }

    /**
     * 生成追踪链接.
     *
     * @param string $originalUrl
     * @param int $trackingId
     *
     * @return string
     */
    public function generateTrackingLink(string $originalUrl, int $trackingId): string
    {
        $token = $this->generateTrackingToken($trackingId);

        return URL::route('mail.track.click', ['tracking_id' => $trackingId]).
               '?url='.urlencode($originalUrl).
               '&token='.$token;
    }

    /**
     * 判断是否为HTML内容.
     *
     * @param string $content
     *
     * @return bool
     */
    private function isHtmlContent(string $content): bool
    {
        return str_contains($content, '<') && strpos($content, '>') !== false;
    }

    /**
     * 向文本内容注入追踪信息.
     */
    private function injectTextTracking(string $content, int $trackingId): string
    {
        // 只重写链接，不添加可见的追踪信息
        return preg_replace_callback(
            '/https?:\/\/[^\s]+/',
            function ($matches) use ($trackingId) {
                return $this->generateTrackingLink($matches[0], $trackingId);
            },
            $content
        );
    }

    /**
     * 生成追踪令牌.
     *
     * @param int $trackingId
     *
     * @return string
     */
    private function generateTrackingToken(int $trackingId): string
    {
        $secret = config('app.key');

        return hash_hmac('sha256', (string) $trackingId, $secret);
    }

    /**
     * 验证追踪令牌.
     */
    public function verifyTrackingToken(int $trackingId, string $token): bool
    {
        $expectedToken = $this->generateTrackingToken($trackingId);

        return hash_equals($expectedToken, $token);
    }

    /**
     * 通用的追踪记录更新方法.
     */
    private function updateTrackingLog(int $trackingId, array $data, ?string $condition = null): bool
    {
        $trackingLog = MailTrackingLog::find($trackingId);
        if (!$trackingLog) {
            return false;
        }

        // 如果有条件检查（避免重复更新）
        if ($condition && $trackingLog->$condition) {
            return true;
        }

        $trackingLog->update($data);

        return true;
    }

    /**
     * 记录邮件打开事件.
     */
    public function recordMailOpen(int $trackingId): bool
    {
        return $this->updateTrackingLog($trackingId, [
            'is_opened' => true,
            'opened_at' => now(),
            'opened_ip' => request()->ip(),
        ], 'is_opened');
    }

    /**
     * 记录链接点击事件.
     */
    public function recordLinkClick(int $trackingId): bool
    {
        return $this->updateTrackingLog($trackingId, [
            'is_clicked' => true,
            'clicked_at' => now(),
            'clicked_ip' => request()->ip(),
            'clicked_url' => request()->get('url'),
        ]);
    }

    /**
     * 记录邮件送达事件.
     */
    public function recordMailDelivered(int $trackingId): bool
    {
        return $this->updateTrackingLog($trackingId, [
            'is_delivered' => true,
            'delivered_at' => now(),
            'mail_provider' => config('mail.default'),
        ]);
    }

    /**
     * 记录邮件退回事件.
     */
    public function recordMailBounced(int $trackingId): bool
    {
        return $this->updateTrackingLog($trackingId, [
            'is_bounced' => true,
            'bounced_at' => now(),
            'mail_provider' => config('mail.default'),
        ]);
    }

    /**
     * 获取追踪统计信息.
     *
     * @param int $taskId
     *
     * @return array
     */
    public function getTrackingStats(int $taskId): array
    {
        $stats = MailTrackingLog::where('task_id', $taskId)
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(is_delivered) as delivered_count,
                SUM(is_opened) as opened_count,
                SUM(is_clicked) as clicked_count,
                SUM(is_bounced) as bounced_count
            ')
            ->first();

        $totalSent = $stats->total_sent ?: 0;

        return [
            'total_sent' => $totalSent,
            'delivered_count' => $stats->delivered_count ?: 0,
            'opened_count' => $stats->opened_count ?: 0,
            'clicked_count' => $stats->clicked_count ?: 0,
            'bounced_count' => $stats->bounced_count ?: 0,
            'delivered_rate' => $totalSent > 0 ? round(($stats->delivered_count / $totalSent) * 100, 2) : 0,
            'open_rate' => $totalSent > 0 ? round(($stats->opened_count / $totalSent) * 100, 2) : 0,
            'click_rate' => $totalSent > 0 ? round(($stats->clicked_count / $totalSent) * 100, 2) : 0,
            'bounce_rate' => $totalSent > 0 ? round(($stats->bounced_count / $totalSent) * 100, 2) : 0,
        ];
    }

    /**
     * 获取追踪记录详情.
     *
     * @param int $trackingId
     *
     * @return MailTrackingLog|null
     */
    public function getTrackingLog(int $trackingId): ?MailTrackingLog
    {
        return MailTrackingLog::find($trackingId);
    }

    /**
     * 获取任务的所有追踪记录.
     *
     * @param int $taskId
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTaskTrackingLogs(int $taskId)
    {
        return MailTrackingLog::where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 处理邮件打开追踪.
     */
    public function handleTrackOpen(int $trackingId, ?string $token): \Illuminate\Http\Response
    {
        if ($this->verifyTrackingToken($trackingId, $token)) {
            $this->recordMailOpen($trackingId);
        }

        return $this->returnTrackingPixel();
    }

    /**
     * 处理链接点击追踪.
     */
    public function handleTrackClick(int $trackingId, ?string $token, ?string $encodedUrl): \Illuminate\Http\RedirectResponse
    {
        // 先解码获取原始URL
        $originalUrl = config('app.url'); // 默认回退URL
        if ($encodedUrl) {
            $decodedUrl = base64_decode($encodedUrl);
            if (filter_var($decodedUrl, FILTER_VALIDATE_URL)) {
                $originalUrl = $decodedUrl;
            }
        }

        // 验证token成功才记录点击事件
        if ($this->verifyTrackingToken($trackingId, $token)) {
            $this->recordLinkClick($trackingId);
        }

        // 无论token是否验证成功，都重定向到原始URL
        return redirect($originalUrl);
    }

    /**
     * 处理送达状态回调.
     */
    public function handleDeliveryCallback(?string $event, ?string $trackingId, ?string $recipient): void
    {
        if (!$trackingId && $recipient) {
            $trackingLog = MailTrackingLog::where('recipient', $recipient)
                ->orderBy('created_at', 'desc')
                ->first();
            $trackingId = $trackingLog?->id;
        }

        if (!$trackingId) {
            return;
        }

        match ($event) {
            'delivered' => $this->recordMailDelivered((int) $trackingId),
            'bounced' => $this->recordMailBounced((int) $trackingId),
            default => null
        };
    }

    /**
     * 返回1x1透明追踪像素.
     */
    private function returnTrackingPixel(): \Illuminate\Http\Response
    {
        $pixelData = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixelData)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache');
    }
}
