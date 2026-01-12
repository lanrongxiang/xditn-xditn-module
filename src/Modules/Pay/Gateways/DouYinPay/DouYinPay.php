<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\DouYinPay;

use Modules\Pay\Contracts\NotifyDataInterface;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Gateways\Concerns\PayGateway;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Pay\Provider\Douyin;
use Yansongda\Supports\Collection;

/**
 * 抖音支付网关.
 *
 * @method Collection mini(array $order) 小程序支付
 */
class DouYinPay extends PayGateway
{
    /**
     * 发起退款.
     */
    public function refund(array $params): mixed
    {
        return $this->instance()->refund([
            'out_order_no' => $params['out_trade_no'],
            'out_refund_no' => $this->createRefundOrderNo(),
            'reason' => $params['reason'] ?? '退款',
            'refund_amount' => $params['amount'],
            '_action' => $params['action'] ?? 'mini',
        ]);
    }

    /**
     * 获取抖音支付实例.
     */
    protected function instance(): Douyin
    {
        $this->loadPayConfig('douyin');

        return PayProvider::douyin();
    }

    /**
     * 获取回调数据解析器.
     */
    protected function getNotifyData(array $data): NotifyDataInterface
    {
        return new DouYinPayNotifyData($data);
    }

    /**
     * 订单号前缀.
     */
    protected function orderNoPrefix(): string
    {
        return 'D';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::DOUYIN;
    }
}
