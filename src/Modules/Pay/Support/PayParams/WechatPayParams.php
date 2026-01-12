<?php

namespace Modules\Pay\Support\PayParams;

/**
 * @see https://pay.yansongda.cn/docs/v3/wechat/pay.html
 */
class WechatPayParams extends PayParams
{
    /**
     * 公众号.
     */
    public function mp(): array
    {
        return array_merge($this->common(), [
            'payer' => [
                'openid' => $this->params['openid'],
            ],
        ]);
    }

    /**
     * h5 支付.
     */
    public function h5(): array
    {
        // 文档 https://pay.yansongda.cn/docs/v3/wechat/pay.html#h5-%E6%94%AF%E4%BB%98
        $default = [
            'scene_info' => [
                'payer_client_ip' => \request()->getClientIp(),
                'h5_info' => [
                    'type' => 'Wap',
                ],
            ],
        ];

        // 用关联的小程序的 https://pay.yansongda.cn/docs/v3/wechat/pay.html#%E5%85%B6%E5%AE%83
        if ($this->params['mini'] ?? false) {
            $default['_type'] = 'mini';
        }

        return array_merge($this->common(), $default);
    }

    public function app(): array
    {
        return $this->common();
    }

    public function mini(): array
    {
        $common = $this->common();

        $common['amount']['currency'] = $this->params['currency'] ?? 'CNY';
        $common['payer'] = [
            'openid' => $this->params['openid'],
        ];

        return $common;
    }

    /**
     * @return array
     */
    public function pos(): array
    {
        return array_merge($this->common(), [
            'payer' => [
                'auth_code' => $this->params['auth_code'],
            ],
            'scene_info' => [
                'id' => $this->params['id'],
            ],
        ]);
    }

    /**
     * @return array
     */
    public function scan(): array
    {
        return $this->common();
    }

    /**
     * @return array
     */
    public function common(): array
    {
        return [
            'out_trade_no' => $this->params['order_no'],
            'description' => $this->params['subject'] ?? '支付',
            'amount' => [
                'total' => $this->params['amount'],
            ],
        ];
    }
}
