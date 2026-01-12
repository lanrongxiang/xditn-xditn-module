<?php

namespace Modules\Pay\Support\PayParams;

/**
 * @see https://pay.yansongda.cn/docs/v3/alipay/pay.html
 */
class AliPayParams extends PayParams
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
        return array_merge($this->common(), [
            'quit_url' => $this->params['quit_url'],
        ]);
    }

    /**
     * app 参数.
     *
     * @return array
     */
    public function app(): array
    {
        return $this->common();
    }

    /**
     * 小程序参数.
     *
     * @return array
     */
    public function mini(): array
    {
        return array_merge($this->common(), [
            'buyer_id' => $this->params['buyer_id'],
        ]);
    }

    /**
     * pos 机参数.
     *
     * @return array
     */
    public function pos(): array
    {
        return array_merge($this->common(), [
            'auth_code' => $this->params['auth_code'],
        ]);
    }    /**
     * 扫码参数.
     *
     * @return array
     */
    public function scan(): array
    {
        return $this->common();
    }

    /**
     * 公共参数.
     *
     * @return array
     */
    protected function common(): array
    {
        return [
            'out_trade_no' => $this->params['order_no'],
            'total_amount' => $this->params['amount'],
            'subject' => $this->params['subject'] ?? '支付',
        ];
    }
}
