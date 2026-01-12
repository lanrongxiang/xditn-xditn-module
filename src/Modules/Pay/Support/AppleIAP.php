<?php

namespace Modules\Pay\Support;

use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\AppleIAPNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;

/**
 * 苹果内购(IAP)支付类.
 */
class AppleIAP extends Pay
{
    /**
     * 退款.
     */
    public function refund(array $params): mixed
    {
        // TODO: 实现Apple IAP退款逻辑
        // Apple IAP退款需要通过App Store Server API处理

        return [
            'refund_id' => 'REFUND_'.time(),
            'status' => 'completed',
        ];
    }

    /**
     * 获取Apple IAP实例.
     */
    protected function instance(): mixed
    {
        // TODO: 初始化Apple IAP SDK
        // 这里需要配置App Store Connect API密钥

        return new \stdClass();
    }

    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new AppleIAPNotifyData($data);
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        return 'IAP';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::APPLE_IAP;
    }

    /**
     * 验证收据.
     */
    public function verifyReceipt(string $receiptData, bool $sandbox = false): array
    {
        // TODO: 实现Apple IAP收据验证
        // 需要调用Apple的验证服务器验证收据

        return [
            'status' => 0, // 0表示成功
            'receipt' => [],
            'latest_receipt_info' => [],
        ];
    }

    /**
     * 创建订阅.
     */
    public function createSubscription(array $params): array
    {
        // TODO: 实现Apple IAP订阅创建
        // Apple IAP订阅需要在App Store Connect中配置产品

        return [
            'product_id' => $params['product_id'],
            'transaction_id' => 'TXN_'.time(),
        ];
    }

    /**
     * 获取订阅状态
     */
    public function getSubscriptionStatus(string $originalTransactionId): array
    {
        // TODO: 实现获取Apple IAP订阅状态
        return [];
    }
}
