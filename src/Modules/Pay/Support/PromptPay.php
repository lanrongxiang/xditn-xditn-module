<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Event;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\NotifyData;
use Modules\Pay\Support\NotifyData\PromptPayNotifyData;

/**
 * PromptPay 支付类.
 *
 * 使用 Omise SDK 实现 PromptPay 支付功能
 * 配置示例：
 * 'promptpay' => [
 *     'api_key' => 'pkey_test_xxx',      // Omise Public Key
 *     'secret_key' => 'skey_test_xxx',   // Omise Secret Key
 *     'mode' => 'sandbox',                // sandbox | live
 * ]
 */
class PromptPay extends Pay
{
    public function __construct(
        protected readonly CurrencyService $currencyService
    ) {
    }

    /**
     * 获取支付实例（兼容父类接口）.
     */
    protected function instance(): mixed
    {
        return new class($this) {
            public function __construct(
                protected PromptPay $promptPay
            ) {
            }

            /**
             * 回调处理.
             */
            public function callback(): mixed
            {
                return new class($this->promptPay) {
                    public function __construct(
                        protected PromptPay $promptPay
                    ) {
                    }

                    public function toArray(): array
                    {
                        // Omise Webhook 发送的是 JSON 格式数据
                        $data = request()->json()->all();

                        // 如果没有 JSON 数据，尝试从 request()->all() 获取
                        if (empty($data)) {
                            $data = request()->all();
                        }

                        return $data;
                    }
                };
            }

            /**
             * 返回成功响应.
             */
            public function success(): string
            {
                return 'success';
            }
        };
    }

    /**
     * 创建支付订单.
     *
     * @param array $params 订单参数
     *
     * @return array 包含 order_no, out_trade_no, qr_code, pay_url
     */
    public function create(array $params): array
    {
        $orderNo = $params['order_no'] ?? $this->createOrderNo();
        $amount = $params['total_amount'] ?? $params['amount'] ?? 0;
        $subject = $params['subject'] ?? '订单支付';
        $currency = $params['currency'] ?? $this->currencyService->getCurrencyCode();

        // 获取支付 URL 配置
        $returnUrl = $params['return_url'] ?? config('pay.promptpay.return_url', '');
        $cancelUrl = $params['cancel_url'] ?? config('pay.promptpay.cancel_url', $returnUrl);
        $notifyUrl = $params['notify_url'] ?? config('pay.promptpay.notify_url', route('pay.callback', ['gateway' => 'promptpay']));

        // 验证金额限制（优先使用平台特定配置，其次使用通用配置）
        $minAmount = config('pay.promptpay.min_recharge_amount', config('pay.general.min_recharge_amount', 100));
        $maxAmount = config('pay.promptpay.max_recharge_amount', config('pay.general.max_recharge_amount', 0));

        if ($amount < $minAmount) {
            throw new \Exception(__('exception.amount_too_small', ['min' => number_format($minAmount / 100, 2)]));
        }

        if ($maxAmount > 0 && $amount > $maxAmount) {
            throw new \Exception(__('exception.amount_too_large', ['max' => number_format($maxAmount / 100, 2)]));
        }

        // 构建请求参数，包含订单信息和请求上下文（IP、method、user_agent 等）
        $requestParams = [
            'order_no' => $orderNo,
            'amount' => $amount,
            'amount_formatted' => number_format($amount / 100, 2, '.', ''),
            'currency' => $currency,
            'subject' => $subject,
        ];

        // 从 Request macro 获取请求上下文信息（如果存在请求对象）
        if (request() && method_exists(request(), 'getRequestInfo')) {
            $requestInfo = request()->getRequestInfo();
            $requestParams = array_merge($requestParams, $requestInfo);
        }

        // 合并其他参数（activity_id、platform 等）
        if (isset($params['activity_id'])) {
            $requestParams['activity_id'] = $params['activity_id'];
        }
        if (isset($params['platform'])) {
            $requestParams['platform'] = $params['platform'];
        }

        try {
            $config = config('pay.promptpay', []);

            // 支持 secret_key 和 api_secret（向后兼容）
            $secretKey = $config['secret_key'] ?? $config['api_secret'] ?? null;

            if (empty($config['api_key']) || empty($secretKey)) {
                throw new \Exception('Omise 配置不完整，请检查 config/pay.php 中的 promptpay 配置。api_key 和 secret_key 不能为空。');
            }

            // 使用 Omise SDK 创建 PromptPay 支付
            $charge = \OmiseCharge::create([
                'amount' => $amount, // Omise 使用分（satang）为单位
                'currency' => strtolower($currency),
                'description' => $subject,
                'source' => [
                    'type' => 'promptpay',
                ],
                'return_uri' => $returnUrl,
                'metadata' => [
                    'order_no' => $orderNo,
                    'notify_url' => $notifyUrl,
                ],
            ], $config['api_key'], $secretKey);

            // 获取二维码和支付链接
            // 注意：二维码是 SVG 格式，可以直接在浏览器中打开或在前端使用 <img> 标签显示
            $qrCode = '';
            $payUrl = '';

            if (isset($charge['source']['scannable_code']['image']['download_uri'])) {
                $qrCode = $charge['source']['scannable_code']['image']['download_uri'];
            }

            // authorize_uri 是 Omise 支付页面链接
            // 用户点击后会跳转到 Omise 支付页面完成支付，支付完成后会重定向到 return_uri
            // 注意：支付状态是通过 Webhook 异步通知的，不是同步返回
            if (isset($charge['authorize_uri'])) {
                $payUrl = $charge['authorize_uri'];
            }

            $result = [
                'order_no' => $orderNo,
                'out_trade_no' => $charge['id'] ?? '',
                'qr_code' => $qrCode,
                'pay_url' => $payUrl,
            ];

            // 注意：事件在控制器层触发，不在支付类中触发

            return $result;
        } catch (\Exception $e) {
            // 注意：事件在控制器层触发，不在支付类中触发
            throw new \Exception('Omise 支付创建失败: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 退款.
     */
    public function refund(array $params): mixed
    {
        $outTradeNo = $params['out_trade_no'] ?? '';
        $amount = $params['amount'] ?? 0;
        $reason = $params['reason'] ?? '退款';

        if (empty($outTradeNo)) {
            throw new \Exception('退款订单号不能为空');
        }

        try {
            $config = config('pay.promptpay', []);

            // 支持 secret_key 和 api_secret（向后兼容）
            $secretKey = $config['secret_key'] ?? $config['api_secret'] ?? null;

            if (empty($config['api_key']) || empty($secretKey)) {
                throw new \Exception('Omise 配置不完整，请检查 config/pay.php 中的 promptpay 配置。api_key 和 secret_key 不能为空。');
            }

            // 获取 Charge 对象
            $charge = \OmiseCharge::retrieve($outTradeNo, $config['api_key'], $secretKey);

            // 创建退款
            $refund = $charge->refunds()->create([
                'amount' => $amount,
                'metadata' => [
                    'reason' => $reason,
                ],
            ]);

            return [
                'id' => $refund['id'] ?? '',
                'amount' => $refund['amount'] ?? $amount,
                'status' => $refund['status'] ?? '',
                'created_at' => $refund['created_at'] ?? '',
            ];
        } catch (\Exception $e) {
            // 注意：事件在控制器层触发，不在支付类中触发
            throw new \Exception('Omise 退款失败: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 查询订单.
     */
    public function query(array $params): array
    {
        $outTradeNo = $params['out_trade_no'] ?? '';

        if (empty($outTradeNo)) {
            throw new \Exception('查询订单号不能为空');
        }

        try {
            $config = config('pay.promptpay', []);

            // 支持 secret_key 和 api_secret（向后兼容）
            $secretKey = $config['secret_key'] ?? $config['api_secret'] ?? null;

            if (empty($config['api_key']) || empty($secretKey)) {
                throw new \Exception('Omise 配置不完整，请检查 config/pay.php 中的 promptpay 配置。api_key 和 secret_key 不能为空。');
            }

            $charge = \OmiseCharge::retrieve($outTradeNo, $config['api_key'], $secretKey);

            return [
                'id' => $charge['id'] ?? '',
                'amount' => $charge['amount'] ?? 0,
                'currency' => $charge['currency'] ?? '',
                'status' => $charge['status'] ?? '',
                'paid' => $charge['paid'] ?? false,
                'paid_at' => $charge['paid_at'] ?? null,
                'created_at' => $charge['created_at'] ?? '',
            ];
        } catch (\Exception $e) {
            // 注意：事件在控制器层触发，不在支付类中触发
            throw new \Exception('Omise 查询失败: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 获取回调数据.
     *
     * Omise 使用 Webhook 发送回调，数据格式为：
     * {
     *   "object": "event",
     *   "id": "evnt_xxx",
     *   "key": "charge.complete",
     *   "data": {
     *     "object": "charge",
     *     "id": "chrg_xxx",
     *     "status": "successful",
     *     "paid": true,
     *     ...
     *   }
     * }
     */
    protected function getNotifyData(array $data): NotifyData
    {
        // 如果是 Webhook 事件格式，提取 data 字段中的 charge 数据
        if (isset($data['object']) && $data['object'] === 'event' && isset($data['data'])) {
            $chargeData = $data['data'];
            // 将事件 key 信息也传递到 charge 数据中，以便 getEventType() 可以使用
            if (isset($data['key'])) {
                $chargeData['key'] = $data['key'];
            }
        } else {
            // 如果直接是 charge 数据，直接使用
            $chargeData = $data;
        }

        return new PromptPayNotifyData($chargeData, 'omise');
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        return 'PP';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::PROMPTPAY;
    }
}
