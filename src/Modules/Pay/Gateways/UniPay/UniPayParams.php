<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\UniPay;

use Modules\Pay\Gateways\Concerns\PayParams;

/**
 * 银联支付参数.
 *
 * @see https://pay.yansongda.cn/docs/v3/unipay/pay.html
 */
class UniPayParams extends PayParams
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
        return $this->common();
    }

    /**
     * 公共参数.
     */
    protected function common(): array
    {
        return [
            'txnTime' => date('YmdHis'),
            'txnAmt' => $this->get('amount'),
            'orderId' => $this->get('order_no'),
        ];
    }
}
