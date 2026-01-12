<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\DouYinPay;

use Modules\Pay\Gateways\Concerns\PayParams;

/**
 * 抖音支付参数.
 *
 * @see https://pay.yansongda.cn/docs/v3/douyin/pay.html
 */
class DouYinPayParams extends PayParams
{
    /**
     * 小程序支付参数.
     */
    public function mini(): array
    {
        return [
            'out_order_no' => $this->get('order_no'),
            'total_amount' => $this->get('amount'),
            'subject' => $this->get('subject', '支付'),
            'body' => $this->get('body', '支付'),
            'valid_time' => $this->get('valid_time', 600),
        ];
    }
}
