<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\WechatPay;

use Modules\Pay\Gateways\Concerns\NotifyData;

/**
 * 微信支付回调数据.
 */
class WechatPayNotifyData extends NotifyData
{
    /**
     * 是否支付成功.
     */
    public function isPaySuccess(): bool
    {
        return $this->getTradeState() === 'success';
    }

    /**
     * 是否退款成功.
     */
    public function isRefundSuccess(): bool
    {
        return $this->getRefundStatus() === 'success';
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        return ($this->data['resource']['original_type'] ?? '') === 'refund';
    }

    /**
     * 获取支付平台订单号.
     */
    public function getOutTradeNo(): string
    {
        return $this->data['resource']['transaction_id'] ?? '';
    }

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string
    {
        return $this->data['resource']['out_trade_no'] ?? '';
    }

    /**
     * 获取交易状态.
     */
    public function getTradeState(): string
    {
        return strtolower($this->data['resource']['trade_state'] ?? '');
    }

    /**
     * 获取退款状态.
     */
    public function getRefundStatus(): string
    {
        return strtolower($this->data['resource']['refund_status'] ?? '');
    }
}
