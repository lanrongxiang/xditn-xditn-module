<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\Concerns;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Pay\Contracts\NotifyDataInterface;
use Modules\Pay\Contracts\PayInterface;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Events\PayEvent;
use Modules\Pay\Events\PayNotifyEvent;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Pay\Pay as PayProvider;

/**
 * 支付网关基类.
 *
 * 所有支付网关实现都应继承此类
 */
abstract class PayGateway implements PayInterface
{
    /**
     * 获取支付实例.
     */
    abstract protected function instance(): mixed;

    /**
     * 获取回调数据解析器.
     */
    abstract protected function getNotifyData(array $data): NotifyDataInterface;

    /**
     * 获取订单号前缀.
     */
    abstract protected function orderNoPrefix(): string;

    /**
     * 获取支付平台枚举.
     */
    abstract protected function platform(): PayPlatform;

    /**
     * 魔术方法处理支付动作.
     *
     * @throws \Random\RandomException
     */
    public function __call(string $name, array $params): mixed
    {
        $params = array_merge(['action' => $name], ...$params);
        $payEvent = new PayEvent($this->createTradeData($params));
        $params = Event::dispatch($payEvent);

        return $this->instance()->{$name}($params[0]);
    }

    /**
     * 处理回调通知.
     *
     * @throws ContainerException
     * @throws InvalidParamsException
     */
    public function notify(): mixed
    {
        $notifyData = $this->instance()->callback()->toArray();
        $notify = $this->getNotifyData($notifyData);

        try {
            Event::dispatch(new PayNotifyEvent($notify));
        } catch (\Throwable $e) {
            // 失败如何处理
        } finally {
            return $this->instance()->success();
        }
    }

    /**
     * 创建订单号.
     *
     * @throws \Random\RandomException
     */
    protected function createOrderNo(): string
    {
        $prefix = $this->orderNoPrefix();

        return $prefix.date('YmdHis').random_int(1000000, 9999999).Str::random(10);
    }

    /**
     * 创建退款订单号.
     *
     * @throws \Random\RandomException
     */
    public function createRefundOrderNo(): string
    {
        return 'R'.$this->createOrderNo();
    }

    /**
     * 创建交易数据.
     *
     * @throws \Random\RandomException
     */
    protected function createTradeData(array $params): array
    {
        $params['order_no'] = $this->createOrderNo();
        $params['platform'] = $this->platform();

        return $params;
    }

    /**
     * 加载支付配置.
     */
    protected function loadPayConfig(string $key): void
    {
        PayProvider::config(PayConfig::get($key));
    }
}
