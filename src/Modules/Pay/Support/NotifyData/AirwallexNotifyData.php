<?php

declare(strict_types=1);

namespace Modules\Pay\Support\NotifyData;

/**
 * Airwallex 回调数据.
 *
 * Airwallex 使用 Webhook 发送事件通知
 * 事件类型包括：payment_intent.succeeded, payment_intent.failed, refund.succeeded 等
 */
class AirwallexNotifyData extends NotifyData implements NotifyDataInterface
{
    /**
     * 是否支付成功
     *
     * Airwallex Webhook 事件：payment_intent.succeeded, payment_attempt.settled
     * 注意：Airwallex 回调数据中，事件类型在 'name' 字段中，而不是 'type' 字段
     */
    public function isPaySuccess(): bool
    {
        // Airwallex 回调数据中，事件类型在 'name' 字段中
        $eventName = $this->data['name'] ?? $this->data['type'] ?? '';
        $object = $this->data['data']['object'] ?? $this->data['object'] ?? [];
        $status = $object['status'] ?? '';

        // 支付意图成功
        if ($eventName === 'payment_intent.succeeded') {
            return strtolower($status) === 'succeeded';
        }

        // 支付捕获完成
        if ($eventName === 'payment_intent.captured') {
            return true;
        }

        // 支付尝试已结算（payment_attempt.settled）
        // 当支付尝试状态为 SETTLED 时，表示支付已成功结算
        if ($eventName === 'payment_attempt.settled') {
            return strtoupper($status) === 'SETTLED';
        }

        return false;
    }

    /**
     * 是否退款成功
     *
     * Airwallex Webhook 事件：refund.succeeded
     */
    public function isRefundSuccess(): bool
    {
        $eventName = $this->data['name'] ?? $this->data['type'] ?? '';
        $object = $this->data['data']['object'] ?? $this->data['object'] ?? [];
        $status = $object['status'] ?? '';

        if ($eventName === 'refund.succeeded') {
            return strtolower($status) === 'succeeded';
        }

        return false;
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        $eventName = $this->data['name'] ?? $this->data['type'] ?? '';

        return strpos($eventName, 'refund') !== false;
    }

    /**
     * 获取支付平台的订单号.
     *
     * Airwallex 支付意图 ID
     */
    public function getOutTradeNo(): string
    {
        $object = $this->data['data']['object'] ?? $this->data['object'] ?? [];

        // 支付意图 ID
        if (isset($object['id'])) {
            return $object['id'];
        }

        // 备用：使用事件 ID
        return $this->data['id'] ?? '';
    }

    /**
     * 获取本地订单号.
     *
     * 从 Airwallex 支付意图的 merchant_order_id 中获取
     * 对于 payment_attempt.settled 事件，需要通过 payment_intent_id 查询支付意图获取 merchant_order_id
     */
    public function getTradeNo(): string
    {
        $object = $this->data['data']['object'] ?? $this->data['object'] ?? [];
        $eventName = $this->data['name'] ?? $this->data['type'] ?? '';

        // 从 merchant_order_id 获取（payment_intent 事件）
        if (isset($object['merchant_order_id'])) {
            return $object['merchant_order_id'];
        }

        // 对于 payment_attempt.settled 事件，尝试从 payment_intent_id 查找
        // 注意：这里返回空字符串，由 AirwallexNotify 通过 payment_intent_id 查找交易记录
        if ($eventName === 'payment_attempt.settled' && isset($object['payment_intent_id'])) {
            // 返回空字符串，让 AirwallexNotify 通过 payment_intent_id 查找
            return '';
        }

        // 从 metadata 获取
        if (isset($object['metadata']['order_no'])) {
            return $object['metadata']['order_no'];
        }

        return '';
    }

    /**
     * 获取支付意图 ID（用于 payment_attempt.settled 事件）.
     */
    public function getPaymentIntentId(): string
    {
        $object = $this->data['data']['object'] ?? $this->data['object'] ?? [];

        return $object['payment_intent_id'] ?? '';
    }

    /**
     * 获取事件类型.
     */
    public function getEventType(): string
    {
        return $this->data['name'] ?? $this->data['type'] ?? '';
    }

    /**
     * 获取资源数据.
     */
    public function getResource(): array
    {
        return $this->data['data']['object'] ?? $this->data['object'] ?? [];
    }
}
