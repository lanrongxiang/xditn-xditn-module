<?php

declare(strict_types=1);

namespace Modules\Pay\Support\PayParams;

/**
 * Airwallex 支付参数.
 */
class AirwallexParams extends PayParams
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
            'subject' => $params['subject'] ?? '订单支付',
            'return_url' => $params['return_url'] ?? '',
            'notify_url' => $params['notify_url'] ?? '',
            'payment_method' => $params['payment_method'] ?? 'card', // card (Visa) 或 paypal
            'customer_email' => $params['customer_email'] ?? '',
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
