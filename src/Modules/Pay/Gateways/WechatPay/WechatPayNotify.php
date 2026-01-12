<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\WechatPay;

use Modules\Pay\Gateways\Concerns\NotifyHandler;

/**
 * 微信支付回调处理.
 */
class WechatPayNotify extends NotifyHandler
{
    /**
     * 退款回调.
     */
    public function refundNotify(): mixed
    {
        // TODO: 实现退款回调逻辑
        return null;
    }

    /**
     * 支付回调.
     */
    public function payNotify(): mixed
    {
        // TODO: 实现支付回调逻辑
        return null;
    }
}
