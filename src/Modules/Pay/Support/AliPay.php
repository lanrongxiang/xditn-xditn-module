<?php

namespace Modules\Pay\Support;

use Laravel\Octane\Exceptions\DdException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\AliPayNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;
use Psr\Http\Message\ResponseInterface;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Rocket;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Supports\Collection;

/**
 * @method ResponseInterface|Rocket web(array $order) 网页支付
 * @method ResponseInterface|Rocket h5(array $order) H5 支付
 * @method ResponseInterface|Rocket app(array $order) APP 支付
 * @method Rocket|Collection mini(array $order) 小程序支付
 * @method Rocket|Collection pos(array $order) 刷卡支付
 * @method Rocket|Collection scan(array $order) 扫码支付
 * @method Rocket|Collection transfer(array $order) 账户转账
 */
class AliPay extends Pay
{
    /**
     * @param array{amount:number,action:"web"|"app"|"mini"|"pos"|"scan"|"h5", out_trade_no:string} $params
     *
     * @throws ContainerException
     * @throws DdException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    public function refund(array $params): mixed
    {
        return $this->instance()->refund([
            'out_trade_no' => $params['out_trade_no'],
            'refund_amount' => $params['amount'],
            '_action' => $params['action'],
        ]);
    }

    /**
     * @return \Yansongda\Pay\Provider\Alipay
     *
     * @throws ContainerException|DdException
     */
    protected function instance(): mixed
    {
        // TODO: Implement instance() method.
        $this->loadPayConfig('alipay');

        return PayProvider::alipay();
    }

    /**
     * 回调数据.
     *
     * @param array $data
     *
     * @return NotifyData
     */
    protected function getNotifyData(array $data): NotifyData
    {
        // TODO: Implement getNotifyData() method.
        return new AliPayNotifyData($data);
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        // TODO: Implement orderNoPrefix() method.
        return 'A';
    }

    protected function platform(): PayPlatform
    {
        return PayPlatform::ALIPAY;
    }
}
