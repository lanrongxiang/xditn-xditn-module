<?php

namespace Modules\Pay\Support;

use Laravel\Octane\Exceptions\DdException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\NotifyData;
use Modules\Pay\Support\NotifyData\UniPayNotifyData;
use Random\RandomException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Supports\Collection;

/**
 * @method Collection web(array $order) 公众号支付
 * @method Collection h5(array $order) H5 支付
 * @method Collection pos(array $order) 刷卡支付
 * @method Collection scan(array $order) 扫码支付
 */
class UniPay extends Pay
{
    /**
     * @param array{amount: int, out_trade_no: string, action:"web"|"qr_code"} $params
     *
     * @throws ContainerException
     * @throws DdException
     * @throws RandomException
     * @throws \Yansongda\Artful\Exception\InvalidParamsException
     * @throws \Yansongda\Artful\Exception\ServiceNotFoundException
     */
    public function refund(array $params): mixed
    {
        // 退款
        return $this->instance()->refund([
            'txnTime' => date('YmdHis'),
            'txnAmt' => $params['amount'],
            'orderId' => $this->createRefundOrderNo(),
            'origQryId' => $params['out_trade_no'],
            '_action' => $params['action'],
        ]);
    }

    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new UniPayNotifyData($data);
    }

    /**
     * @return \Yansongda\Pay\Provider\Alipay
     *
     * @throws ContainerException|DdException
     */
    protected function instance(): mixed
    {
        // TODO: Implement instance() method.
        $this->loadPayConfig('unipay');

        return PayProvider::unipay();
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        // TODO: Implement orderNoPrefix() method.
        return 'U';
    }

    protected function platform(): PayPlatform
    {
        return PayPlatform::UNIPAY;
    }
}
