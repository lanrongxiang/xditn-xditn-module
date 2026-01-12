<?php

namespace Modules\Pay\Listeners;

use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Events\PayEvent;
use Modules\Pay\Models\Order;
use Modules\Pay\Support\PayParams\AirwallexParams;
use Modules\Pay\Support\PayParams\AliPayParams;
use Modules\Pay\Support\PayParams\DouYinPayParams;
use Modules\Pay\Support\PayParams\PayPalParams;
use Modules\Pay\Support\PayParams\UniPayParams;
use Modules\Pay\Support\PayParams\WechatPayParams;

class PayListener
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
     * @param PayEvent $event
     *
     * @return array
     */
    public function handle(PayEvent $event): array
    {
        //
        $params = $event->params;

        $order = Order::createNew([
            'subject_id' => $params['subject_id'],
            'order_no' => $params['order_no'],
            'amount' => $params['amount'],
            'platform' => $params['platform'],
            'user_id' => $params['user_id'],
            'action' => $params['action'],
            'pay_status' => PayStatus::PENDING,
        ]);

        if ($order) {
            return match ($params['platform']) {
                PayPlatform::ALIPAY => (new AliPayParams($params))->{$params['action']}(),
                PayPlatform::UNIPAY => (new UniPayParams($params))->{$params['action']}(),
                PayPlatform::WECHAT => (new WechatPayParams($params))->{$params['action']}(),
                PayPlatform::DOUYIN => (new DouyinPayParams($params))->{$params['action']}(),
                PayPlatform::PAYPAL => (new PayPalParams($params))->{$params['action']}(),
                PayPlatform::AIRWALLEX => (new AirwallexParams($params))->{$params['action']}(),
                default => [],
            };
        }

        return [];
    }
}
