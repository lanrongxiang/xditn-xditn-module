<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\UniPay;

use Modules\Pay\Gateways\Concerns\NotifyData;

/**
 * 银联支付回调数据.
 */
class UniPayNotifyData extends NotifyData
{
    /**
     * 是否支付成功.
     */
    public function isPaySuccess(): bool
    {
        // TODO: 根据银联回调数据格式实现
        return ($this->data['respCode'] ?? '') === '00';
    }

    /**
     * 是否退款成功.
     */
    public function isRefundSuccess(): bool
    {
        // TODO: 根据银联回调数据格式实现
        return ($this->data['respCode'] ?? '') === '00';
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        // TODO: 根据银联回调数据格式实现
        return ($this->data['txnType'] ?? '') === '04';
    }

    /**
     * 获取支付平台订单号.
     */
    public function getOutTradeNo(): string
    {
        return $this->data['queryId'] ?? '';
    }

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string
    {
        return $this->data['orderId'] ?? '';
    }
}
