<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

/**
 * 退款动作枚举.
 */
enum RefundAction: string implements Enum
{
    use EnumTrait;

    case APP = 'app';
    case H5 = 'h5';
    case MINI = 'mini';
    case WEB = 'web'; // 支付宝
    case POS = 'pos'; // 支付宝
    case SCAN = 'scan'; // 支付宝
    case JSAPI = 'jsapi'; // 微信公众号支付
    case COMBINE = 'combine'; // 微信组合支付
    case NATIVE = 'native'; // 微信原生支付
    case QR_CODE = 'qr_code'; // 银联二维码

    /**
     * Get the action label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::APP => 'APP',
            self::H5 => 'H5',
            self::MINI => '小程序',
            self::WEB => 'WEB',
            self::POS => 'POS',
            self::SCAN => 'SCAN',
            self::JSAPI => 'JSAPI',
            self::COMBINE => 'COMBINE',
            self::NATIVE => 'NATIVE',
            self::QR_CODE => 'QR_CODE',
        };
    }
}
