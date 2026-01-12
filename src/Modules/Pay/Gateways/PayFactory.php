<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways;

use Illuminate\Container\Container;
use Modules\Pay\Contracts\PayInterface;
use Modules\Pay\Enums\PayPlatform as Pay;
use Modules\Pay\Exceptions\PayPlatformNotSupportException;
use Modules\Pay\Gateways\AliPay\AliPay;
use Modules\Pay\Gateways\DouYinPay\DouYinPay;
use Modules\Pay\Gateways\UniPay\UniPay;
use Modules\Pay\Gateways\WechatPay\WechatPay;

/**
 * 支付工厂类.
 *
 * 用于创建支付网关实例
 */
class PayFactory
{
    /**
     * 创建支付网关实例.
     */
    public static function make(Pay $platform): PayInterface
    {
        $container = Container::getInstance();

        return match ($platform) {
            Pay::ALIPAY => $container->make(AliPay::class),
            Pay::WECHAT => $container->make(WechatPay::class),
            Pay::UNIPAY => $container->make(UniPay::class),
            Pay::DOUYIN => $container->make(DouYinPay::class),
            default => throw new PayPlatformNotSupportException("支付平台 {$platform->label()} 不支持，请在应用中扩展实现")
        };
    }
}
