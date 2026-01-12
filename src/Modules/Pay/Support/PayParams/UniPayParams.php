<?php

namespace Modules\Pay\Support\PayParams;

/**
 * @see https://pay.yansongda.cn/docs/v3/unipay/pay.html
 */
class UniPayParams extends PayParams
{
    /**
     * @return array
     */
    public function web(): array
    {
        return $this->common();
    }

    /**
     * @return array
     */
    public function h5(): array
    {
        return $this->common();
    }

    /**
     * @return array
     */
    public function common(): array
    {
        return [
            'txnTime' => date('YmdHis'),
            'txnAmt' => $this->params['amount'],
            'orderId' => $this->params['order_no'],
        ];
    }
}
