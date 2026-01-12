<?php

declare(strict_types=1);

namespace Modules\Pay\Contracts;

/**
 * 支付回调数据接口.
 *
 * 所有支付平台的回调数据解析类都必须实现此接口
 */
interface NotifyDataInterface
{
    /**
     * 是否支付成功.
     */
    public function isPaySuccess(): bool;

    /**
     * 是否退款成功.
     */
    public function isRefundSuccess(): bool;

    /**
     * 是否是退款.
     */
    public function isRefund(): bool;

    /**
     * 获取支付平台的订单号.
     */
    public function getOutTradeNo(): string;

    /**
     * 获取本地订单号.
     */
    public function getTradeNo(): string;
}
