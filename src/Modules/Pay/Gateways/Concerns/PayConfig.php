<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\Concerns;

use Modules\Openapi\Exceptions\FailedException;

/**
 * 支付配置管理.
 */
class PayConfig
{
    /**
     * 获取支付配置.
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
            $platforms = ['alipay', 'wechat', 'unipay', 'douyin'];

            foreach ($platforms as $platform) {
                if ($config = config('pay.'.$platform)) {
                    $payConfig[$platform] = [
                        'default' => $config,
                    ];
                }
            }
        }

        // 处理证书路径
        foreach ($payConfig as $platform => &$config) {
            if (!$config) {
                continue;
            }

            foreach ($config['default'] as $k => $value) {
                if (is_string($value) && str_starts_with($value, 'certs')) {
                    if (!file_exists($certPath = storage_path($value))) {
                        throw new FailedException(__('exception.certificate_file_not_found', ['platform' => $platform, 'key' => $k, 'path' => $value]));
                    }

                    $config['default'][$k] = $certPath;
                }
            }
        }

        // HTTP 配置
        $payConfig['http'] = [
            'timeout' => 5.0,
            'verify' => false,
        ];

        return $payConfig;
    }
}
