<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use Illuminate\Container\Container;
use Modules\Pay\Enums\PayPlatform as Pay;
use Modules\Pay\Exceptions\PayPlatformNotSupportException;

/**
 * @class PayFactory
 *
 * @desc 支付工厂类
 */
class PayFactory
{
    /**
     * @param Pay $platform
     *
     * @return PayInterface
     */
    public static function make(Pay $platform): PayInterface
    {
        // 使用 Laravel 容器解析依赖，支持依赖注入
        $container = Container::getInstance();

        return match ($platform) {
            Pay::WECHAT => $container->make(WechatPay::class),
            Pay::ALIPAY => $container->make(AliPay::class),
            Pay::UNIPAY => $container->make(UniPay::class),
            Pay::DOUYIN => $container->make(DouYinPay::class),
            Pay::PAYPAL => $container->make(PayPal::class),
            Pay::APPLE_PAY => $container->make(ApplePay::class),
            Pay::APPLE_IAP => $container->make(AppleIAP::class),
            Pay::PROMPTPAY => $container->make(PromptPay::class),
            Pay::AIRWALLEX => $container->make(Airwallex::class),
            Pay::HAIPAY => $container->make(HaiPay::class),
            default => throw new PayPlatformNotSupportException("支付平台{$platform->value()}不支持")
        };
    }
}
