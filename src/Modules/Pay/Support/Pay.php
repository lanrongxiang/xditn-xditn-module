<?php

namespace Modules\Pay\Support;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Octane\Exceptions\DdException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Events\PayEvent;
use Modules\Pay\Events\PayNotifyEvent;
use Modules\Pay\Support\NotifyData\NotifyData;
use Random\RandomException;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Pay\Pay as PayProvider;

/**
 * Class Pay.
 */
abstract class Pay implements PayInterface
{
    /**
     * 支付实例.
     */
    abstract protected function instance(): mixed;

    /**
     * @return mixed
     *
     * @throws RandomException
     */
    public function __call($name, $params)
    {
        $params = array_merge(['action' => $name], ...$params);
        $payEvent = new PayEvent($this->createTradeData($params));
        $params = Event::dispatch($payEvent);

        return $this->instance()->{$name}($params[0]);
    }

    /**
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
     * 获取回调数据.
     */
    abstract protected function getNotifyData(array $data): NotifyData;

    /**
     * 订单号前缀
     */
    abstract protected function orderNoPrefix(): string;

    /**
     * @return PayPlatform
     */
    abstract protected function platform(): PayPlatform;

    /**
     * 创建订单号.
     *
     * @throws RandomException
     */
    protected function createOrderNo(): string
    {
        $prefix = $this->orderNoPrefix();

        return $prefix.date('YmdHis').random_int(1000000, 9999999).Str::random(10);
    }

    /**
     * 创建退款订单号，退款订单号加个 R 字符.
     *
     * @throws RandomException
     */
    public function createRefundOrderNo(): string
    {
        return 'R'.$this->createOrderNo();
    }

    /**
     * @return array|string[]
     *
     * @throws RandomException
     */
    protected function createTradeData(array $params): array
    {
        $params['order_no'] = $this->createOrderNo();

        $params['platform'] = $this->platform();

        return $params;

    }

    /**
     * 加载支付配置.
     *
     * @throws ContainerException|DdException
     */
    protected function loadPayConfig(string $key): void
    {
        PayProvider::config(PayConfig::get($key));
    }
}
