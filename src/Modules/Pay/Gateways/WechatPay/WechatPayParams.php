<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\WechatPay;

use Modules\Pay\Gateways\Concerns\PayParams;

/**
 * 微信支付参数.
 *
 * @see https://pay.yansongda.cn/docs/v3/wechat/pay.html
 */
class WechatPayParams extends PayParams
{
    /**
     * 公众号支付参数.
     */
    public function mp(): array
    {
        return array_merge($this->common(), [
            'payer' => [
                'openid' => $this->get('openid'),
            ],
        ]);
    }

    /**
     * H5 支付参数.
     */
    public function h5(): array
    {
        $default = [
            'scene_info' => [
                'payer_client_ip' => request()->getClientIp(),
                'h5_info' => [
                    'type' => 'Wap',
                ],
            ],
        ];

        if ($this->get('mini', false)) {
            $default['_type'] = 'mini';
        }

        return array_merge($this->common(), $default);
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
        $common = $this->common();
        $common['amount']['currency'] = $this->get('currency', 'CNY');
        $common['payer'] = [
            'openid' => $this->get('openid'),
        ];

        return $common;
    }

    /**
     * POS 机支付参数.
     */
    public function pos(): array
    {
        return array_merge($this->common(), [
            'payer' => [
                'auth_code' => $this->get('auth_code'),
            ],
            'scene_info' => [
                'id' => $this->get('id'),
            ],
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
            'description' => $this->get('subject', '支付'),
            'amount' => [
                'total' => $this->get('amount'),
            ],
        ];
    }
}
