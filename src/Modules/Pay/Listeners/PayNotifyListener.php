<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Pay\Events\PayNotifyEvent;
use Modules\Pay\Gateways\AliPay\AliPayNotify;
use Modules\Pay\Gateways\AliPay\AliPayNotifyData;
use Modules\Pay\Gateways\DouYinPay\DouYinPayNotify;
use Modules\Pay\Gateways\DouYinPay\DouYinPayNotifyData;
use Modules\Pay\Gateways\UniPay\UniPayNotify;
use Modules\Pay\Gateways\UniPay\UniPayNotifyData;
use Modules\Pay\Gateways\WechatPay\WechatPayNotify;
use Modules\Pay\Gateways\WechatPay\WechatPayNotifyData;

/**
 * 支付回调监听器.
 *
 * 处理各支付平台的回调通知
 */
class PayNotifyListener
{
    /**
     * Handle the event.
     *
     * @throws \Throwable
     */
    public function handle(PayNotifyEvent $event): void
    {
        $notifyData = $event->data;

        Log::channel('payment')->info('PayNotifyListener 处理回调', [
            'notify_data_type' => get_class($notifyData),
        ]);

        if ($notifyData instanceof AliPayNotifyData) {
            (new AliPayNotify($notifyData))->notify();
        }

        if ($notifyData instanceof WechatPayNotifyData) {
            (new WechatPayNotify($notifyData))->notify();
        }

        if ($notifyData instanceof UniPayNotifyData) {
            (new UniPayNotify($notifyData))->notify();
        }

        if ($notifyData instanceof DouYinPayNotifyData) {
            (new DouYinPayNotify($notifyData))->notify();
        }
    }
}
