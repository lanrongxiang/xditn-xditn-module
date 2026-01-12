<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use App\Support\DataSanitizer;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Events\PaymentCompleted;

/**
 * 支付完成日志监听器.
 */
class PaymentCompletedLogListener
{
    public function handle(PaymentCompleted $event): void
    {
        if (!config('pay.general.enable_log', true)) {
            return;
        }

        try {
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

            Log::channel('payment')->info('支付完成事件', [
                // 订单信息
                'order_no' => $event->orderNo,
                'out_trade_no' => $event->outTradeNo,
                'platform' => $event->platform,
                // 事件数据
                'event_data' => $event->data,
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
