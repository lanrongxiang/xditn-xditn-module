<?php

namespace Modules\Pay\Support;

use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\ApplePayNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;

/**
 * Apple Pay支付类.
 */
class ApplePay extends Pay
{
    /**
     * 退款.
     */
    public function refund(array $params): mixed
    {
        // TODO: 实现Apple Pay退款逻辑
        // Apple Pay退款需要通过Apple Pay API处理

        return [
            'refund_id' => 'REFUND_'.time(),
            'status' => 'completed',
        ];
    }

    /**
     * 获取Apple Pay实例.
     */
    protected function instance(): mixed
    {
        // TODO: 初始化Apple Pay SDK
        // 这里需要配置Apple Pay的证书和密钥

        return new \stdClass();
    }

    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new ApplePayNotifyData($data);
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        return 'AP';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::APPLE_PAY;
    }
}
