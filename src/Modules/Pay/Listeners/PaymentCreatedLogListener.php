<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use App\Support\DataSanitizer;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Events\PaymentCreated;

/**
 * 支付创建日志监听器.
 */
class PaymentCreatedLogListener
{
    public function handle(PaymentCreated $event): void
    {
        if (!config('pay.general.enable_log', true)) {
            return;
        }

        try {
            Log::channel('payment')->info('支付创建', [
                // 订单信息
                'order_id' => $event->request['order_no'] ?? $event->request['order_id'] ?? null,
                'platform' => $event->platform,
                'user_id' => $event->request['user_id'] ?? null,
                'amount' => $event->request['amount'] ?? null,
                'currency' => $event->request['currency'] ?? null,
                'subject' => $event->request['subject'] ?? null,
                // 请求信息
                'request_method' => $event->request['method'] ?? null,
                'request_ip' => $event->request['ip'] ?? null,
                'request_user_agent' => $event->request['user_agent'] ?? null,
                'request_body' => DataSanitizer::sanitizeRequestData($event->request),
                // 响应信息
                'response' => $event->response,
            ]);
        } catch (\Throwable $e) {
            if (app()->environment() !== 'testing') {
                throw $e;
            }
        }
    }

}
