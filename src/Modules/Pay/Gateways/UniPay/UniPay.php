<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\UniPay;

use Modules\Pay\Contracts\NotifyDataInterface;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Gateways\Concerns\PayGateway;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Supports\Collection;

/**
 * 银联支付网关.
 *
 * @method Collection web(array $order) 网页支付
 * @method Collection h5(array $order) H5 支付
 * @method Collection pos(array $order) 刷卡支付
 * @method Collection scan(array $order) 扫码支付
 */
class UniPay extends PayGateway
{
    /**
     * 发起退款.
     */
    public function refund(array $params): mixed
    {
        return $this->instance()->refund([
            'txnTime' => date('YmdHis'),
            'txnAmt' => $params['amount'],
            'orderId' => $this->createRefundOrderNo(),
            'origQryId' => $params['out_trade_no'],
            '_action' => $params['action'],
        ]);
    }

    /**
     * 获取银联支付实例.
     */
    protected function instance(): mixed
    {
        $this->loadPayConfig('unipay');

        return PayProvider::unipay();
    }

    /**
     * 获取回调数据解析器.
     */
    protected function getNotifyData(array $data): NotifyDataInterface
    {
        return new UniPayNotifyData($data);
    }

    /**
     * 订单号前缀.
     */
    protected function orderNoPrefix(): string
    {
        return 'U';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::UNIPAY;
    }
}
