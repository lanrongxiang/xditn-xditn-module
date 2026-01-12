<?php

declare(strict_types=1);

namespace Modules\Pay\Support\NotifyData;

/**
 * PromptPay 回调数据.
 *
 * 使用 Omise SDK 处理回调
 */
class PromptPayNotifyData extends NotifyData implements NotifyDataInterface
{
    public function __construct(array $data, string $channel = 'omise')
    {
        parent::__construct($data);
    }

    /**
     * 是否支付成功
     *
     * Omise Charge 状态：
     * - pending: 待处理
     * - successful: 支付成功
     * - failed: 支付失败
     *
     * 同时检查 paid 字段（boolean）和 status 字段
     */
    public function isPaySuccess(): bool
    {
        // 检查 paid 字段（boolean）
        if (isset($this->data['paid']) && $this->data['paid'] === true) {
            return true;
        }

        // 检查 status 字段
        $status = strtolower($this->data['status'] ?? '');

        return $status === 'successful';
    }

    /**
     * 是否退款成功
     */
    public function isRefundSuccess(): bool
    {
        $status = $this->data['status'] ?? '';

        return strtolower($status) === 'refunded';
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        return isset($this->data['refunded']) || str_contains(strtolower($this->data['status'] ?? ''), 'refund');
    }

    /**
     * 获取支付平台的订单号（Omise Charge ID）.
     */
    public function getOutTradeNo(): string
    {
        return $this->data['id'] ?? '';
    }

    /**
     * 获取本地订单号.
     *
     * 从 metadata.order_no 获取，这是创建订单时设置的
     */
    public function getTradeNo(): string
    {
        // 优先从 metadata.order_no 获取
        if (isset($this->data['metadata']['order_no'])) {
            return (string) $this->data['metadata']['order_no'];
        }

        // 备用：从 description 获取（如果 description 包含订单号）
        $description = $this->data['description'] ?? '';
        if (!empty($description)) {
            return $description;
        }

        return '';
    }

    /**
     * 获取事件类型.
     *
     * Omise Webhook 事件类型：
     * - charge.create: 创建 Charge
     * - charge.complete: Charge 完成（支付成功）
     * - charge.capture: Charge 捕获
     * - charge.failure: Charge 失败
     */
    public function getEventType(): string
    {
        // 如果数据来自 Webhook 事件，优先使用 key 字段
        if (isset($this->data['key'])) {
            return $this->data['key'];
        }

        // 否则使用 object 字段
        return $this->data['object'] ?? 'charge';
    }

    /**
     * 获取资源数据.
     */
    public function getResource(): array
    {
        return $this->data;
    }

    /**
     * 获取通道（兼容性方法，始终返回 'omise'）.
     */
    public function getChannel(): string
    {
        return 'omise';
    }
}
