<?php

namespace Modules\Pay\Support;

use Laravel\Octane\Exceptions\DdException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\DouYinPayNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;
use Random\RandomException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Pay\Provider\Douyin;
use Yansongda\Supports\Collection;

/**
 * @method Collection mini(array $order) 小程序支付
 */
class DouYinPay extends Pay
{
    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new DouYinPayNotifyData($data);
    }

    /**
     * @return Douyin
     *
     * @throws ContainerException
     * @throws DdException
     */
    protected function instance(): mixed
    {
        // TODO: Implement instance() method.
        $this->loadPayConfig('douyin');

        return PayProvider::douyin();
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        // TODO: Implement orderNoPrefix() method.
        return 'D';
    }

    /**
     * @param array{amount: int, action: string, out_trade_no: string, reason?: string} $params
     *
     * @throws ContainerException
     * @throws DdException
     * @throws RandomException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
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

    protected function platform(): PayPlatform
    {
        return PayPlatform::DOUYIN;
    }
}
