<?php

namespace Modules\Pay\Support\NotifyData;

interface NotifyDataInterface
{
    /**
     * 是否支付成功
     *
     * @return bool
     */
    public function isPaySuccess(): bool;

    /**
     * 是否退款成功
     *
     * @return bool
     */
    public function isRefundSuccess(): bool;

    /**
     * 是否是退款.
     *
     * @return bool
     */
    public function isRefund(): bool;    /**
     * 获取支付平台的订单号.
     *
     * @return string
     */
    public function getOutTradeNo(): string;    /**
     * 获取本地订单号.
     *
     * @return string
     */
    public function getTradeNo(): string;
}
