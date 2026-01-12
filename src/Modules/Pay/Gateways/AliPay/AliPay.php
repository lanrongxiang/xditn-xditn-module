<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\AliPay;

use Modules\Pay\Contracts\NotifyDataInterface;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Gateways\Concerns\PayGateway;
use Psr\Http\Message\ResponseInterface;
use Yansongda\Artful\Rocket;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Supports\Collection;

/**
 * 支付宝支付网关.
 *
 * @method ResponseInterface|Rocket web(array $order) 网页支付
 * @method ResponseInterface|Rocket h5(array $order) H5 支付
 * @method ResponseInterface|Rocket app(array $order) APP 支付
 * @method Rocket|Collection mini(array $order) 小程序支付
 * @method Rocket|Collection pos(array $order) 刷卡支付
 * @method Rocket|Collection scan(array $order) 扫码支付
 * @method Rocket|Collection transfer(array $order) 账户转账
 */
class AliPay extends PayGateway
{
    /**
     * 发起退款.
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
     * 获取支付宝实例.
     */
    protected function instance(): mixed
    {
        $this->loadPayConfig('alipay');

        return PayProvider::alipay();
    }

    /**
     * 获取回调数据解析器.
     */
    protected function getNotifyData(array $data): NotifyDataInterface
    {
        return new AliPayNotifyData($data);
    }

    /**
     * 订单号前缀.
     */
    protected function orderNoPrefix(): string
    {
        return 'A';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::ALIPAY;
    }
}
