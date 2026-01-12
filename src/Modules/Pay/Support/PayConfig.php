<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use Laravel\Octane\Exceptions\DdException;
use Modules\Openapi\Exceptions\FailedException;

/**
 * 支付配置.
 */
class PayConfig
{
    /**
     * @return array[]
     *
     * @throws DdException
     */
    public static function get(?string $key = null): array
    {
        $payConfig = [];

        if ($key) {
            if (!config('pay.'.$key)) {
                throw new FailedException(__('exception.payment_config_not_found'));
            }

            $payConfig[$key] = [
                'default' => config('pay.'.$key),
            ];
        } else {
            if ($alipayConfig = config('pay.alipay')) {
                $payConfig['alipay'] = [
                    'default' => $alipayConfig,
                ];
            }

            if ($wechatConfig = config('pay.wechat')) {
                $payConfig['wechat'] = [
                    'default' => $wechatConfig,
                ];
            }

            if ($unipayConfig = config('pay.unipay')) {
                $payConfig['unipay'] = [
                    'default' => $unipayConfig,
                ];
            }

            if ($douyinConfig = config('pay.douyin')) {
                $payConfig['douyin'] = [
                    'default' => $douyinConfig,
                ];
            }

            if ($paypalConfig = config('pay.paypal')) {
                $payConfig['paypal'] = [
                    'default' => $paypalConfig,
                ];
            }

            if ($promptpayConfig = config('pay.promptpay')) {
                $payConfig['promptpay'] = [
                    'default' => $promptpayConfig,
                ];
            }

            if ($airwallexConfig = config('pay.airwallex')) {
                $payConfig['airwallex'] = [
                    'default' => $airwallexConfig,
                ];
            }

            if ($haiPayConfig = config('pay.haipay')) {
                $payConfig['haipay'] = [
                    'default' => $haiPayConfig,
                ];
            }
        }

        foreach ($payConfig as $platform => &$config) {
            if (!$config) {
                continue;
            }

            foreach ($config['default'] as $k => $value) {
                // 如果以 certs 开头,则替换为绝对路径
                if (is_string($value) && str_starts_with($value, 'certs')) {
                    if (!file_exists($certPath = storage_path($value))) {
                        throw new FailedException(__('exception.certificate_file_not_found', ['platform' => $platform, 'key' => $k, 'path' => $value]));
                    }

                    $config['default'][$k] = $certPath;
                }
            }
        }

        // 设置 http 配置
        $payConfig['http'] = [
            'timeout' => 5.0,
            'verify' => false,
        ];

        return $payConfig;
    }
}
