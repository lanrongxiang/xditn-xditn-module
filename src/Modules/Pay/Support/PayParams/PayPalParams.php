<?php

declare(strict_types=1);

namespace Modules\Pay\Support\PayParams;

/**
 * PayPal支付参数.
 */
class PayPalParams extends PayParams
{
    /**
     * 创建支付订单.
     */
    public function create(): array
    {
        $params = $this->params;

        return [
            'out_trade_no' => $params['order_no'],
            'total_amount' => $params['amount'],
            'subject' => $params['subject'] ?? '金币充值',
            'return_url' => $params['return_url'] ?? '',
            'notify_url' => $params['notify_url'] ?? '',
        ];
    }

    /**
     * 查询订单.
     */
    public function query(): array
    {
        $params = $this->params;

        return [
            'out_trade_no' => $params['order_no'],
        ];
    }

    /**
     * 关闭订单.
     */
    public function close(): array
    {
        $params = $this->params;

        return [
            'out_trade_no' => $params['order_no'],
        ];
    }
}
