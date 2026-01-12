<?php

namespace Modules\Pay\Support\NotifyData;

/**
 * 微信回调数据.
 */
class AliPayNotifyData extends NotifyData
{
    /**
     * 是否支付成功
     */
    public function isPaySuccess(): bool
    {
        return $this->getTradeState() == 'trade_success';
    }

    /**
     * 是否退款成功
     */
    public function isRefundSuccess(): bool
    {
        // TODO: Implement isRefundSuccess() method.
        return $this->getRefundStatus() == 'success';
    }

    /**
     * 获取退款状态
     */
    public function getRefundStatus(): string
    {
        return strtolower($this->data['resource']['refund_status']);
    }

    /**
     * 交易是否完成.
     *
     * @return bool
     */
    public function isTradeFinished(): bool
    {
        return $this->getTradeState() == 'trade_finished';
    }

    /**
     * 交易是否等待付款.
     *
     * @return bool
     */
    public function isWaitBuyerPay(): bool
    {
        return $this->getTradeState() == 'wait_buyer_pay';
    }

    /**
     * 交易是否关闭.
     *
     * @return bool
     */
    public function isTradeClose(): bool
    {
        return $this->getTradeState() == 'trade_closed';
    }

    /**
     * 获取交易状态
     *
     * @return string
     */
    public function getTradeState(): string
    {
        return strtolower($this->data['trade_status']);
    }

    /**
     * 是否是退款回调.
     */
    public function isRefund(): bool
    {
        return $this->data['resource']['original_type'] == 'refund';
    }

    /**
     * 获取平台交易订单号.
     *
     * @return string
     */
    public function getOutTradeNo(): string
    {
        // TODO: Implement getOutTradeNo() method.
        return $this->data['trade_no'];
    }

    /**
     * 获取本地交易订单号.
     *
     * @return string
     */
    public function getTradeNo(): string
    {
        // TODO: Implement getTradeNo() method.
        return $this->data['out_trade_no'];
    }
}
