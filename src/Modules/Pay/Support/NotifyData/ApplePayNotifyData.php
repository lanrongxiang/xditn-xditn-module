<?php

namespace Modules\Pay\Support\NotifyData;

/**
 * Apple Pay回调数据.
 */
class ApplePayNotifyData extends NotifyData implements NotifyDataInterface
{
    /**
     * 是否支付成功
     */
    public function isPaySuccess(): bool
    {
        return isset($this->data['status']) &&
               strtolower($this->data['status']) === 'approved';
    }

    /**
     * 是否退款成功
     */
    public function isRefundSuccess(): bool
    {
        return isset($this->data['refund_status']) &&
               strtolower($this->data['refund_status']) === 'completed';
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        return isset($this->data['transaction_type']) &&
               strpos($this->data['transaction_type'], 'REFUND') !== false;
    }

    /**
     * 获取支付平台的订单号.
     */
    public function getOutTradeNo(): string
    {
        return $this->data['transaction_id'] ?? $this->data['id'] ?? '';
    }

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string
    {
        return $this->data['order_id'] ?? $this->data['merchant_order_id'] ?? '';
    }
}
