<?php

namespace Modules\Pay\Support\NotifyData;

/**
 * 苹果内购回调数据.
 */
class AppleIAPNotifyData extends NotifyData implements NotifyDataInterface
{
    /**
     * 是否支付成功
     */
    public function isPaySuccess(): bool
    {
        // Apple IAP的notification_type为INITIAL_BUY或DID_RENEW表示支付成功
        return isset($this->data['notification_type']) &&
               in_array($this->data['notification_type'], ['INITIAL_BUY', 'DID_RENEW']);
    }

    /**
     * 是否退款成功
     */
    public function isRefundSuccess(): bool
    {
        return isset($this->data['notification_type']) &&
               $this->data['notification_type'] === 'REFUND';
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        return isset($this->data['notification_type']) &&
               $this->data['notification_type'] === 'REFUND';
    }

    /**
     * 获取支付平台的订单号.
     */
    public function getOutTradeNo(): string
    {
        return $this->data['transaction_id'] ?? $this->data['original_transaction_id'] ?? '';
    }

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string
    {
        return $this->data['custom'] ?? $this->data['order_id'] ?? '';
    }

    /**
     * 获取产品ID.
     */
    public function getProductId(): string
    {
        return $this->data['product_id'] ?? '';
    }

    /**
     * 获取订阅状态
     */
    public function getSubscriptionStatus(): string
    {
        return $this->data['notification_type'] ?? '';
    }
}
