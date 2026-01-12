<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum PayPlatform: int implements Enum
{
    use EnumTrait;

    case ALIPAY = 1;
    case WECHAT = 2;
    case UNIPAY = 3;
    case DOUYIN = 4;
    case PAYPAL = 5;
    case APPLE_PAY = 6;
    case APPLE_IAP = 7;
    case PROMPTPAY = 8;
    case AIRWALLEX = 9;
    case HAIPAY = 10;

    /**
     * Get the platform label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::ALIPAY => '支付宝',
            self::WECHAT => '微信',
            self::UNIPAY => '银联',
            self::DOUYIN => '抖音',
            self::PAYPAL => 'PayPal',
            self::APPLE_PAY => 'Apple Pay',
            self::APPLE_IAP => '苹果内购',
            self::PROMPTPAY => 'PromptPay',
            self::AIRWALLEX => 'Airwallex',
            self::HAIPAY => 'HaiPay',
        };
    }

    /**
     * Get the platform identifier (lowercase string for API/logging).
     *
     * @return string 平台标识符，如 'paypal', 'alipay', 'wechat' 等
     */
    public function identifier(): string
    {
        return match ($this) {
            self::ALIPAY => 'alipay',
            self::WECHAT => 'wechat',
            self::UNIPAY => 'unipay',
            self::DOUYIN => 'douyin',
            self::PAYPAL => 'paypal',
            self::APPLE_PAY => 'apple_pay',
            self::APPLE_IAP => 'apple_iap',
            self::PROMPTPAY => 'promptpay',
            self::AIRWALLEX => 'airwallex',
            self::HAIPAY => 'haipay',
        };
    }

    /**
     * 从网关标识符（gateway）转换为 PayPlatform.
     *
     * @param string $gateway 网关标识符，如 'paypal', 'promptpay'
     *
     * @return PayPlatform|null 返回对应的 PayPlatform，如果不存在则返回 null
     */
    public static function fromGateway(string $gateway): ?self
    {
        return match (strtolower($gateway)) {
            'paypal' => self::PAYPAL,
            'promptpay' => self::PROMPTPAY,
            'airwallex' => self::AIRWALLEX,
            'haipay' => self::HAIPAY,
            'haipay_cashier' => self::HAIPAY,
            'haipay-paypal' => self::HAIPAY,
            'haipay_paypal_payout' => self::HAIPAY,
            default => null,
        };
    }

    /**
     * 从平台标识符（identifier）转换为 PayPlatform.
     *
     * @param string $identifier 平台标识符，如 'paypal', 'promptpay', 'alipay' 等
     *
     * @return PayPlatform|null 返回对应的 PayPlatform，如果不存在则返回 null
     */
    public static function fromIdentifier(string $identifier): ?self
    {
        return match (strtolower($identifier)) {
            'alipay' => self::ALIPAY,
            'wechat' => self::WECHAT,
            'unipay' => self::UNIPAY,
            'douyin' => self::DOUYIN,
            'paypal' => self::PAYPAL,
            'apple_pay' => self::APPLE_PAY,
            'apple_iap' => self::APPLE_IAP,
            'promptpay' => self::PROMPTPAY,
            'airwallex' => self::AIRWALLEX,
            'haipay' => self::HAIPAY,
            'haipay_cashier' => self::HAIPAY,
            'haipay_paypal_payout' => self::HAIPAY,
            default => null,
        };
    }

    /**
     * 获取默认支付平台（从配置读取）.
     *
     * @return PayPlatform 返回默认支付平台
     */
    public static function getDefault(): self
    {
        $defaultPayMethod = config('pay.general.default_pay_method', 'promptpay');

        return match (strtolower($defaultPayMethod)) {
            'paypal' => self::PAYPAL,
            'airwallex' => self::AIRWALLEX,
            'haipay' => self::HAIPAY,
            'haipay_cashier' => self::HAIPAY,
            default => self::PROMPTPAY,
        };
    }
}
