<?php

declare(strict_types=1);

namespace Modules\Mail\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Mail\Support\MailTrackingService;

/**
 * 邮件追踪控制器
 * 简单处理邮件打开和链接点击追踪.
 */
class MailTrackingController extends Controller
{
    public function __construct(
        protected MailTrackingService $trackingService
    ) {
    }

    /**
     * 邮件打开追踪 - 返回1x1透明图片.
     */
    public function trackOpen(Request $request, int $trackingId): Response
    {
        $token = $request->get('token');

        return $this->trackingService->handleTrackOpen($trackingId, $token);
    }

    /**
     * 链接点击追踪.
     */
    public function trackClick(Request $request, int $trackingId): RedirectResponse
    {
        $token = $request->get('token');
        $encodedUrl = $request->get('url');

        return $this->trackingService->handleTrackClick($trackingId, $token, $encodedUrl);
    }

    /**
     * 邮件送达状态回调（用于SMTP服务商回调）.
     */
    public function handleDeliveryCallback(Request $request): void
    {
        $this->trackingService->handleDeliveryCallback(
            $request->get('event'),
            $request->get('tracking_id'),
            $request->get('recipient')
        );
    }
}
