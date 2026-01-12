<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\AliPay;

use Modules\Pay\Gateways\Concerns\PayParams;

/**
 * 支付宝支付参数.
 *
 * @see https://pay.yansongda.cn/docs/v3/alipay/pay.html
 */
class AliPayParams extends PayParams
{
    /**
     * 网页支付参数.
     */
    public function web(): array
    {
        return $this->common();
    }

    /**
     * H5 支付参数.
     */
    public function h5(): array
    {
        return array_merge($this->common(), [
            'quit_url' => $this->get('quit_url'),
        ]);
    }

    /**
     * APP 支付参数.
     */
    public function app(): array
    {
        return $this->common();
    }

    /**
     * 小程序支付参数.
     */
    public function mini(): array
    {
        return array_merge($this->common(), [
            'buyer_id' => $this->get('buyer_id'),
        ]);
    }

    /**
     * POS 机支付参数.
     */
    public function pos(): array
    {
        return array_merge($this->common(), [
            'auth_code' => $this->get('auth_code'),
        ]);
    }

    /**
     * 扫码支付参数.
     */
    public function scan(): array
    {
        return $this->common();
    }

    /**
     * 公共参数.
     */
    protected function common(): array
    {
        return [
            'out_trade_no' => $this->get('order_no'),
            'total_amount' => $this->get('amount'),
            'subject' => $this->get('subject', '支付'),
        ];
    }
}
