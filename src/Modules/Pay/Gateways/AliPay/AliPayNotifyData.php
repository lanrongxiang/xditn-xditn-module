<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\AliPay;

use Modules\Pay\Gateways\Concerns\NotifyData;

/**
 * 支付宝回调数据.
 */
class AliPayNotifyData extends NotifyData
{
    /**
     * 是否支付成功.
     */
    public function isPaySuccess(): bool
    {
        return $this->getTradeState() === 'trade_success';
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
        return $this->data['trade_no'] ?? '';
    }

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string
    {
        return $this->data['out_trade_no'] ?? '';
    }

    /**
     * 获取交易状态.
     */
    public function getTradeState(): string
    {
        return strtolower($this->data['trade_status'] ?? '');
    }

    /**
     * 获取退款状态.
     */
    public function getRefundStatus(): string
    {
        return strtolower($this->data['resource']['refund_status'] ?? '');
    }

    /**
     * 交易是否完成.
     */
    public function isTradeFinished(): bool
    {
        return $this->getTradeState() === 'trade_finished';
    }

    /**
     * 交易是否等待付款.
     */
    public function isWaitBuyerPay(): bool
    {
        return $this->getTradeState() === 'wait_buyer_pay';
    }

    /**
     * 交易是否关闭.
     */
    public function isTradeClose(): bool
    {
        return $this->getTradeState() === 'trade_closed';
    }
}
