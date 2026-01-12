<?php

namespace Modules\Pay\Support\PayParams;

/**
 * @see https://pay.yansongda.cn/docs/v3/douyin/pay.html
 */
class DouYinPayParams extends PayParams
{
    public function mini(): array
    {
        return [
            'out_order_no' => $this->params['order_no'],
            'total_amount' => $this->params['amount'],
            'subject' => $this->params['subject'] ?? '支付',
            'body' => $this->params['body'] ?? '支付',
            'valid_time' => $this->params['valid_time'] ?? 600,
        ];
    }
}
