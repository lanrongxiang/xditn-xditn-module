<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\WechatPay;

use Modules\Pay\Contracts\NotifyDataInterface;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Gateways\Concerns\PayGateway;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Pay\Provider\Wechat;
use Yansongda\Supports\Collection;

/**
 * 微信支付网关.
 *
 * @method Collection mp(array $order) 公众号支付
 * @method Collection h5(array $order) H5 支付
 * @method Collection app(array $order) APP 支付
 * @method Collection mini(array $order) 小程序支付
 * @method Collection pos(array $order) 刷卡支付
 * @method Collection scan(array $order) 扫码支付
 * @method Collection transfer(array $order) 转账
 */
class WechatPay extends PayGateway
{
    /**
     * 发起退款.
     */
    public function refund(array $params): mixed
    {
        return $this->instance()->refund([
            'out_trade_no' => $params['out_trade_no'],
            'out_refund_no' => $this->createRefundOrderNo(),
            'reason' => $params['reason'] ?? '退款',
            'amount' => [
                'refund' => $params['amount'],
                'total' => $params['total_amount'] ?? $params['amount'],
                'currency' => $params['currency'] ?? 'CNY',
            ],
            '_action' => $params['action'],
        ]);
    }

    /**
     * 获取微信支付实例.
     */
    protected function instance(): Wechat
    {
        $this->loadPayConfig('wechat');

        return PayProvider::wechat();
    }

    /**
     * 获取回调数据解析器.
     */
    protected function getNotifyData(array $data): NotifyDataInterface
    {
        return new WechatPayNotifyData($data);
    }

    /**
     * 订单号前缀.
     */
    protected function orderNoPrefix(): string
    {
        return 'W';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::WECHAT;
    }
}
