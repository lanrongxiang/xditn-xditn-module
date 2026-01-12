<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\DouYinPay;

use Modules\Pay\Gateways\Concerns\NotifyData;

/**
 * 抖音支付回调数据.
 */
class DouYinPayNotifyData extends NotifyData
{
    /**
     * 是否支付成功.
     */
    public function isPaySuccess(): bool
    {
        // TODO: 根据抖音回调数据格式实现
        return ($this->data['status'] ?? '') === 'SUCCESS';
    }

    /**
     * 是否退款成功.
     */
    public function isRefundSuccess(): bool
    {
        // TODO: 根据抖音回调数据格式实现
        return ($this->data['refund_status'] ?? '') === 'SUCCESS';
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        // TODO: 根据抖音回调数据格式实现
        return isset($this->data['refund_no']);
    }

    /**
     * 获取支付平台订单号.
     */
    public function getOutTradeNo(): string
    {
        return $this->data['payment_order_no'] ?? '';
    }

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string
    {
        return $this->data['out_order_no'] ?? '';
    }
}
