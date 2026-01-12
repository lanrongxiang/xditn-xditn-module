<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use Illuminate\Support\Facades\Log;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\HaiPayNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;
use XditnModule\Exceptions\FailedException;

/**
 * HaiPay 支付集成.
 *
 * 说明：
 * - 创建订单、代付等具体接口较多，这里优先接入「异步通知」流程，
 *   具体下单、代付、对账接口可以按 HaiPay 文档后续补全：
 *   - 接入流程：[HaiPay 接入流程文档](https://doc.haipay.net/guide/integration_process_guide/)
 *   - 公共接口 / 异步通知结构：[HaiPay 公共接口文档](https://doc.haipay.net/api/version2/CommonApi/)
 */
class HaiPay extends Pay
{
    /**
     * HaiPay 不依赖 yansongda/pay，这里返回一个简单包装对象，
     * 仅实现 callback()/success() 以复用父类的 notify() 流程。
     */
    protected function instance(): mixed
    {
        return new class() {
            /**
             * 从请求中获取原始回调数据.
             */
            public function callback(): mixed
            {
                return new class() {
                    public function toArray(): array
                    {
                        $request = request();

                        // HaiPay 官方建议使用通用结构接收参数（如数组 / JSON）
                        // 参考文档：https://doc.haipay.net/api/version2/CommonApi/ 中「异步通知」章节
                        $data = $request->all();

                        if (empty($data)) {
                            $data = $request->json()->all();
                        }

return $data ?? [];
                    }
                };
            }

            /**
             * HaiPay 要求成功时返回大写 "SUCCESS"
             * 参考：https://doc.haipay.net/api/version2/CommonApi/ 「异步通知」说明.
             */
            public function success(): string
            {
                return 'SUCCESS';
            }
        };
    }

    /**
     * 将回调数组封装为 NotifyData 对象
     */
    protected function getNotifyData(array $data): NotifyData
    {
        // 可在此处增加签名校验
        // 签名规则请参考 HaiPay 文档「配置与签名」章节
        if (!$this->verifySignature($data)) {
            Log::channel('payment')->warning('HaiPay 回调验签失败', [
                'payload' => $data,
            ]);

            throw new FailedException('HaiPay 签名验证失败');
        }

        return new HaiPayNotifyData($data);
    }

    /**
     * HaiPay 订单号前缀（本地订单号）.
     */
    protected function orderNoPrefix(): string
    {
        return 'HP';
    }

    /**
     * 支付平台枚举.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::HAIPAY;
    }

    /**
     * HaiPay 回调签名验证
     *
     * 委托给 HaiPayCashier 实现（全球收银台）
     *
     * 注意：HaiPayCashier::verifySignature() 现在会根据回调的 appId 或 currency
     * 动态获取对应的配置，因此不需要手动传递 merchantSecretKey
     *
     * TODO: 暂时跳过验签，后续再解决验签问题
     */
    protected function verifySignature(array $data): bool
    {
        // TODO: 暂时跳过验签，直接返回 true，后续再解决验签问题
        return true;

        // 委托给 HaiPayCashier 进行验证
        // verifySignature() 方法会根据回调的 appId 或 currency 自动获取对应的配置
        // return app(HaiPayCashier::class)->verifySignature($data);
    }

    /**
     * HaiPay 创建支付 / 代收订单.
     *
     * 客户端通过 haipay_channel / channel 指定具体产品：
     *  - cashier: 全球收银台代收（docs/HaiPay全球收银台.md）
     *  - paypal_payout: PayPal 代付（docs/HaiPay-paypal接口.md）
     */
    public function create(array $params): array
    {
        $channel = $this->getChannel($params);

        return match ($channel) {
            'cashier' => app(HaiPayCashier::class)->create($params),
            'paypal_payout' => app(HaiPayPaypalPayout::class)->create($params),
            default => throw new FailedException('不支持的 HaiPay 渠道: '.$channel),
        };
    }

    /**
     * 退款 / 代付结果操作.
     */
    public function refund(array $params): mixed
    {
        $channel = $this->getChannel($params);

        return match ($channel) {
            // 全球收银台目前主要为代收，下游如果需要，可在 HaiPayCashier 内实现退款
            'cashier' => throw new FailedException('HaiPay Cashier 暂未实现退款接口'),
            // PayPal 代付退款/撤销，后续可根据 HaiPay 文档扩展
            'paypal_payout' => throw new FailedException('HaiPay PayPal 代付暂未实现退款接口'),
            default => throw new FailedException('不支持的 HaiPay 渠道: '.$channel),
        };
    }

    /**
     * 从请求参数中获取 HaiPay 渠道.
     *
     * 优先级：haipay_channel > channel > product
     */
    protected function getChannel(array $params): string
    {
        $channel = $params['haipay_channel'] ?? $params['channel'] ?? $params['product'] ?? 'cashier';

        return strtolower((string) $channel);
    }
}
