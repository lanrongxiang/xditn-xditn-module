<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use App\Support\DataSanitizer;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Events\PayNotifyEvent;

/**
 * 支付回调事件日志监听器.
 */
class PayNotifyLogListener
{
    public function handle(PayNotifyEvent $event): void
    {
        if (!config('pay.general.enable_log', true)) {
            return;
        }

        try {
            $notifyData = $event->data;

            // 尝试从当前请求获取信息（如果是回调请求）
            $requestInfo = [];
            if (request()) {
                $requestInfo = [
                    'request_method' => request()->method(),
                    'request_ip' => request()->ip(),
                    'request_user_agent' => request()->userAgent(),
                    'request_url' => request()->fullUrl(),
                ];

                // 如果是回调请求，记录请求体
                if (request()->isMethod('POST')) {
                    $requestInfo['request_body'] = DataSanitizer::sanitizeRequestData(request()->all());
                }
            }

            Log::channel('payment')->info('支付回调事件', [
                // 回调数据信息
                'order_no' => method_exists($notifyData, 'getTradeNo') ? $notifyData->getTradeNo() : null,
                'out_trade_no' => method_exists($notifyData, 'getOutTradeNo') ? $notifyData->getOutTradeNo() : null,
                'platform' => method_exists($notifyData, 'getPlatform') ? $notifyData->getPlatform() : null,
                'is_pay_success' => method_exists($notifyData, 'isPaySuccess') ? $notifyData->isPaySuccess() : null,
                'is_refund' => method_exists($notifyData, 'isRefund') ? $notifyData->isRefund() : null,
                // 请求信息（如果有）
                ...$requestInfo,
            ]);
        } catch (\Throwable $e) {
            if (app()->environment() !== 'testing') {
                throw $e;
            }
        }
    }

}
