<?php

declare(strict_types=1);

namespace Modules\Pay\Support\NotifyData;

/**
 * HaiPay 异步通知数据.
 *
 * 回调字段参考：
 * https://doc.haipay.net/api/version2/CommonApi/ 「异步通知」中的代收回调参数
 */
class HaiPayNotifyData extends NotifyData implements NotifyDataInterface
{
    /**
     * 是否支付成功
     *
     * HaiPay status：
     * - 2 成功
     * - 3 失败
     * - 4 部分收款（稳定币）
     * - 5 超额收款（稳定币）
     * - 1 支付中（收银台文档），此处不视为成功
     */
    public function isPaySuccess(): bool
    {
        $status = (int) ($this->data['status'] ?? 0);

        return in_array($status, [2, 4, 5], true);
    }

    /**
     * 是否退款通知（HaiPay 公共文档主要描述代收回调，这里默认不是退款）.
     */
    public function isRefund(): bool
    {
        return false;
    }

    /**
     * 是否退款成功
     */
    public function isRefundSuccess(): bool
    {
        return false;
    }

    /**
     * 平台订单号（orderNo）.
     */
    public function getOutTradeNo(): string
    {
        return (string) ($this->data['orderNo'] ?? '');
    }

    /**
     * 商户订单号（orderId）.
     */
    public function getTradeNo(): string
    {
        return (string) ($this->data['orderId'] ?? '');
    }

    /**
     * 获取支付金额（字符串，单位与 HaiPay 文档一致）.
     */
    public function getAmount(): string
    {
        return (string) ($this->data['amount'] ?? '0');
    }

    /**
     * 获取状态码
     */
    public function getStatus(): int
    {
        return (int) ($this->data['status'] ?? 0);
    }

    /**
     * 获取错误信息.
     */
    public function getErrorMessage(): string
    {
        return (string) ($this->data['errorMsg'] ?? '');
    }

    /**
     * 获取回调原始数据.
     */
    public function getRaw(): array
    {
        return $this->data;
    }
}
