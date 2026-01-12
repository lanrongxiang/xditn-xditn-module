<?php

declare(strict_types=1);

namespace Modules\Pay\Support\NotifyData;

/**
 * PayPal回调数据.
 *
 * PayPal 使用 Webhook 发送事件通知
 * 事件类型包括：PAYMENT.CAPTURE.COMPLETED, PAYMENT.CAPTURE.REFUNDED 等
 */
class PayPalNotifyData extends NotifyData implements NotifyDataInterface
{
    /**
     * 是否支付成功
     *
     * PayPal Webhook 事件：PAYMENT.CAPTURE.COMPLETED, CHECKOUT.ORDER.COMPLETED
     * 注意：CHECKOUT.ORDER.APPROVED 不是支付成功事件，只是订单被批准，需要后续捕获
     */
    public function isPaySuccess(): bool
    {
        $eventType = $this->data['event_type'] ?? '';
        $resource = $this->data['resource'] ?? [];
        $status = $resource['status'] ?? '';

        // 支付捕获完成（这是真正的支付成功事件）
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            return strtolower($status) === 'completed';
        }

        // 订单完成（订单已捕获完成）
        if ($eventType === 'CHECKOUT.ORDER.COMPLETED') {
            // 检查订单状态是否为 COMPLETED
            return strtoupper($status) === 'COMPLETED';
        }

        return false;
    }

    /**
     * 是否退款成功
     *
     * PayPal Webhook 事件：PAYMENT.CAPTURE.REFUNDED
     */
    public function isRefundSuccess(): bool
    {
        $eventType = $this->data['event_type'] ?? '';
        $resource = $this->data['resource'] ?? [];
        $status = $resource['status'] ?? '';

        if ($eventType === 'PAYMENT.CAPTURE.REFUNDED') {
            return strtolower($status) === 'completed';
        }

        return false;
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        $eventType = $this->data['event_type'] ?? '';

        return strpos($eventType, 'REFUND') !== false ||
               strpos($eventType, 'REFUNDED') !== false;
    }

    /**
     * 获取支付平台的订单号.
     *
     * PayPal 订单 ID 或捕获 ID
     */
    public function getOutTradeNo(): string
    {
        $resource = $this->data['resource'] ?? [];

        // 捕获 ID（支付交易 ID）
        if (isset($resource['id'])) {
            return $resource['id'];
        }

        // 订单 ID
        if (isset($resource['supplementary_data']['related_ids']['order_id'])) {
            return $resource['supplementary_data']['related_ids']['order_id'];
        }

        // 备用：使用事件 ID
        return $this->data['id'] ?? '';
    }

    /**
     * 获取本地订单号.
     *
     * 从 PayPal 订单的 custom_id 或 invoice_id 中获取
     *
     * 注意：不同事件类型的 resource 结构不同：
     * - CHECKOUT.ORDER.APPROVED: resource.purchase_units[0].custom_id
     * - PAYMENT.CAPTURE.COMPLETED: resource.custom_id
     */
    public function getTradeNo(): string
    {
        $resource = $this->data['resource'] ?? [];
        $eventType = $this->data['event_type'] ?? '';

        // 对于 PAYMENT.CAPTURE.COMPLETED 事件，custom_id 直接在 resource 中
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' || $eventType === 'PAYMENT.CAPTURE.REFUNDED') {
            if (isset($resource['custom_id']) && !empty($resource['custom_id'])) {
                return (string) $resource['custom_id'];
            }
        }

        // 对于 CHECKOUT.ORDER.* 事件，custom_id 在 purchase_units 中
        if (isset($resource['purchase_units'][0]['custom_id']) && !empty($resource['purchase_units'][0]['custom_id'])) {
            return $resource['purchase_units'][0]['custom_id'];
        }

        // 从 invoice_id 获取
        if (isset($resource['invoice_id']) && !empty($resource['invoice_id'])) {
            return $resource['invoice_id'];
        }

        // 从 supplementary_data 获取
        if (isset($resource['supplementary_data']['related_ids']['invoice_id']) && !empty($resource['supplementary_data']['related_ids']['invoice_id'])) {
            return $resource['supplementary_data']['related_ids']['invoice_id'];
        }

        return '';
    }

    /**
     * 获取事件类型.
     */
    public function getEventType(): string
    {
        return $this->data['event_type'] ?? '';
    }

    /**
     * 获取资源数据.
     */
    public function getResource(): array
    {
        return $this->data['resource'] ?? [];
    }
}
