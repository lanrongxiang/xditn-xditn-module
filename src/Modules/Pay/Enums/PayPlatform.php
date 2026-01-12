<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

/**
 * 支付平台枚举（基础版本）.
 *
 * 仅包含基础的国内支付平台，其他支付平台（如 PayPal、Apple Pay、Airwallex 等）
 * 请在应用项目中扩展此枚举或创建自定义枚举
 */
enum PayPlatform: int implements Enum
{
    use EnumTrait;

    case ALIPAY = 1;
    case WECHAT = 2;
    case UNIPAY = 3;
    case DOUYIN = 4;

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
        };
    }

    /**
     * Get the platform identifier (lowercase string for API/logging).
     *
     * @return string 平台标识符，如 'alipay', 'wechat' 等
     */
    public function identifier(): string
    {
        return match ($this) {
            self::ALIPAY => 'alipay',
            self::WECHAT => 'wechat',
            self::UNIPAY => 'unipay',
            self::DOUYIN => 'douyin',
        };
    }

    /**
     * 从网关标识符（gateway）转换为 PayPlatform.
     *
     * @param string $gateway 网关标识符
     *
     * @return PayPlatform|null 返回对应的 PayPlatform，如果不存在则返回 null
     */
    public static function fromGateway(string $gateway): ?self
    {
        return match (strtolower($gateway)) {
            'alipay' => self::ALIPAY,
            'wechat' => self::WECHAT,
            'unipay' => self::UNIPAY,
            'douyin' => self::DOUYIN,
            default => null,
        };
    }

    /**
     * 从平台标识符（identifier）转换为 PayPlatform.
     *
     * @param string $identifier 平台标识符
     *
     * @return PayPlatform|null 返回对应的 PayPlatform，如果不存在则返回 null
     */
    public static function fromIdentifier(string $identifier): ?self
    {
        return self::fromGateway($identifier);
    }

    /**
     * 获取默认支付平台（从配置读取）.
     *
     * @return PayPlatform 返回默认支付平台
     */
    public static function getDefault(): self
    {
        $defaultPayMethod = config('pay.general.default_pay_method', 'alipay');

        return self::fromGateway($defaultPayMethod) ?? self::ALIPAY;
    }
}
