<?php

declare(strict_types=1);

namespace Modules\Pay\Listeners;

use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Events\PayEvent;
use Modules\Pay\Gateways\AliPay\AliPayParams;
use Modules\Pay\Gateways\DouYinPay\DouYinPayParams;
use Modules\Pay\Gateways\UniPay\UniPayParams;
use Modules\Pay\Gateways\WechatPay\WechatPayParams;
use Modules\Pay\Models\Order;

/**
 * 支付事件监听器.
 *
 * 处理支付创建事件
 */
class PayListener
{
    /**
     * Handle the event.
     */
    public function handle(PayEvent $event): array
    {
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
                PayPlatform::WECHAT => (new WechatPayParams($params))->{$params['action']}(),
                PayPlatform::UNIPAY => (new UniPayParams($params))->{$params['action']}(),
                PayPlatform::DOUYIN => (new DouYinPayParams($params))->{$params['action']}(),
                default => [],
            };
        }

        return [];
    }
}
