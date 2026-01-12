<?php

namespace Modules\Pay\Listeners;

use Modules\Pay\Events\PayNotifyEvent;
use Modules\Pay\Support\Notify\AirwallexNotify;
use Modules\Pay\Support\Notify\AliPayNotify;
use Modules\Pay\Support\Notify\DouYinPayNotify;
use Modules\Pay\Support\Notify\HaiPayNotify;
use Modules\Pay\Support\Notify\PayPalNotify;
use Modules\Pay\Support\Notify\PromptPayNotify;
use Modules\Pay\Support\Notify\UniPayNotify;
use Modules\Pay\Support\Notify\WechatPayNotify;
use Modules\Pay\Support\NotifyData\AirwallexNotifyData;
use Modules\Pay\Support\NotifyData\AliPayNotifyData;
use Modules\Pay\Support\NotifyData\DouYinPayNotifyData;
use Modules\Pay\Support\NotifyData\HaiPayNotifyData;
use Modules\Pay\Support\NotifyData\PayPalNotifyData;
use Modules\Pay\Support\NotifyData\PromptPayNotifyData;
use Modules\Pay\Support\NotifyData\UniPayNotifyData;
use Modules\Pay\Support\NotifyData\WechatPayNotifyData;

class PayNotifyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param PayNotifyEvent $event
     *
     * @throws \Throwable
     */
    public function handle(PayNotifyEvent $event): void
    {
        $notifyData = $event->data;

        \Illuminate\Support\Facades\Log::channel('payment')->info('PayNotifyListener 处理回调', [
            'notify_data_type' => get_class($notifyData),
            'is_airwallex' => $notifyData instanceof AirwallexNotifyData,
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

        if ($notifyData instanceof PayPalNotifyData) {
            \Illuminate\Support\Facades\Log::channel('payment')->info('PayNotifyListener 调用 PayPalNotify', [
                'trade_no' => $notifyData->getTradeNo(),
                'out_trade_no' => $notifyData->getOutTradeNo(),
                'event_type' => $notifyData->getEventType(),
                'is_pay_success' => $notifyData->isPaySuccess(),
            ]);
            (new PayPalNotify($notifyData))->notify();
        }

        if ($notifyData instanceof PromptPayNotifyData) {
            (new PromptPayNotify($notifyData))->notify();
        }

        if ($notifyData instanceof AirwallexNotifyData) {
            \Illuminate\Support\Facades\Log::channel('payment')->info('PayNotifyListener 调用 AirwallexNotify', [
                'trade_no' => $notifyData->getTradeNo(),
                'out_trade_no' => $notifyData->getOutTradeNo(),
                'event_type' => $notifyData->getEventType(),
                'is_pay_success' => $notifyData->isPaySuccess(),
            ]);
            (new AirwallexNotify($notifyData))->notify();
        }

        if ($notifyData instanceof HaiPayNotifyData) {
            \Illuminate\Support\Facades\Log::channel('payment')->info('PayNotifyListener 调用 HaiPayNotify', [
                'trade_no' => $notifyData->getTradeNo(),
                'out_trade_no' => $notifyData->getOutTradeNo(),
                'status' => $notifyData->getStatus(),
                'is_pay_success' => $notifyData->isPaySuccess(),
            ]);

            (new HaiPayNotify($notifyData))->notify();
        }
    }
}
