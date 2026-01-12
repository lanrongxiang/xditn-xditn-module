<?php

namespace Modules\Pay\Support;

use App\Services\CurrencyService;
use Laravel\Octane\Exceptions\DdException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\NotifyData;
use Modules\Pay\Support\NotifyData\WechatPayNotifyData;
use Random\RandomException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Pay\Pay as PayProvider;
use Yansongda\Pay\Provider\Wechat;
use Yansongda\Supports\Collection;

/**
 * @method Collection mp(array $order) 公众号支付
 * @method Collection h5(array $order) H5 支付
 * @method Collection app(array $order) APP 支付
 * @method Collection mini(array $order) 小程序支付
 * @method Collection pos(array $order) 刷卡支付
 * @method Collection scan(array $order) 扫码支付
 * @method Collection transfer(array $order) 转账
 */
class WechatPay extends Pay
{
    public function __construct(
        protected readonly CurrencyService $currencyService
    ) {
    }
    /**
     * @param array{out_trade_no: string, amount: int, total_amount?: int, currency?: string, action: "jsapi"|"app"|"combine"|"h5"|"mini"|"native", reason?: string} $params
     *
     * @throws ContainerException
     * @throws DdException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     * @throws RandomException
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
                // 微信支付只支持 CNY，但如果系统配置了 CNY 则使用系统配置
                'currency' => $params['currency'] ?? ($this->currencyService->getCurrencyCode() === 'CNY' ? 'CNY' : 'CNY'),
            ],
            '_action' => $params['action'],
        ]);
    }

    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new WechatPayNotifyData($data);
    }

    /**
     * @return Wechat
     *
     * @throws ContainerException
     * @throws DdException
     */
    protected function instance(): mixed
    {
        // TODO: Implement instance() method.
        $this->loadPayConfig('wechat');

        return PayProvider::wechat();
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        // TODO: Implement orderNoPrefix() method.
        return 'W';
    }

    protected function platform(): PayPlatform
    {
        return PayPlatform::WECHAT;
    }
}
