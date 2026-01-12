<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\AirwallexNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;
use XditnModule\Exceptions\FailedException;

/**
 * Airwallex 支付类.
 *
 * 仅支持 Visa/Mastercard 等信用卡支付
 * PayPal 支付请使用 PayPal 支付类
 * 参考文档：https://www.airwallex.com/docs/api
 */
class Airwallex extends Pay
{
    protected ?string $accessToken = null;

    /**
     * 获取访问令牌.
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $config = config('pay.airwallex', []);

        if (empty($config['client_id']) || empty($config['api_key'])) {
            throw new FailedException('Airwallex 配置不完整，请检查 config/pay.php 中的 airwallex 配置。client_id 和 api_key 不能为空。');
        }

        $mode = $config['mode'] ?? 'sandbox';
        // 支持多种模式判断：0 或 'sandbox' 表示沙盒，1 或 'live'/'production' 表示生产
        $isProduction = ($mode === 'live' || $mode === 'production' || $mode === 1 || $mode === '1');
        $baseUrl = $isProduction
            ? 'https://api.airwallex.com'
            : 'https://api-demo.airwallex.com';

        try {
            $response = Http::withHeaders([
                'x-api-key' => $config['api_key'],
                'x-client-id' => $config['client_id'],
            ])->post($baseUrl.'/api/v1/authentication/login', []);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::channel('payment')->error('Airwallex 认证失败', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);
                throw new FailedException('Airwallex 认证失败: '.$errorBody);
            }

            $data = $response->json();
            $this->accessToken = $data['token'] ?? '';

            if (empty($this->accessToken)) {
                Log::channel('payment')->error('Airwallex 认证失败：未获取到访问令牌');
                throw new FailedException('Airwallex 认证失败：未获取到访问令牌');
            }

            return $this->accessToken;
        } catch (\Exception $e) {
            if (!($e instanceof FailedException)) {
                Log::channel('payment')->error('Airwallex 认证异常', [
                    'error' => $e->getMessage(),
                ]);
            }

            throw new FailedException('Airwallex 认证失败: '.$e->getMessage());
        }
    }

    /**
     * 获取 API 基础 URL.
     */
    protected function getBaseUrl(): string
    {
        $config = config('pay.airwallex', []);
        $mode = $config['mode'] ?? 'sandbox';
        // 支持多种模式判断：0 或 'sandbox' 表示沙盒，1 或 'live'/'production' 表示生产
        $isProduction = ($mode === 'live' || $mode === 'production' || $mode === 1 || $mode === '1');

        return $isProduction
            ? 'https://api.airwallex.com'
            : 'https://api-demo.airwallex.com';
    }

    /**
     * 获取支付实例.
     */
    protected function instance(): mixed
    {
        // 返回一个包装对象，实现必要的接口方法以兼容父类
        return new class($this) {
            public function __construct(
                protected Airwallex $airwallex
            ) {
            }

            /**
             * 回调处理.
             */
            public function callback(): mixed
            {
                return new class() {
                    public function toArray(): array
                    {
                        $request = request();
                        $data = $request->all();

                        // 如果没有数据，尝试从 JSON 获取
                        if (empty($data)) {
                            $data = $request->json()->all();
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
     * @return array 包含 order_no, out_trade_no, approval_url, pay_url
     */
    public function create(array $params): array
    {
        $config = config('pay.airwallex', []);

        $orderNo = $params['order_no'] ?? $this->createOrderNo();
        // 金额单位：传入的 amount 是分（整数），需要转换为元（小数）
        $amount = $params['total_amount'] ?? $params['amount'] ?? 0;

        // 确保金额是整数（分）
        $amount = (int) $amount;

        // 记录金额转换日志

        // 验证金额范围（如果配置了）
        if (isset($config['min_recharge_amount']) && $amount < ($config['min_recharge_amount'] * 100)) {
            $currencyService = app(CurrencyService::class);
            $currencyCode = $config['currency'] ?? $currencyService->getCurrencyCode();
            $currencyUnit = $currencyService->getCurrencyUnit($currencyCode) ?: $currencyService->getCurrencyName($currencyCode) ?: '元';
            throw new FailedException('充值金额不能少于 '.$config['min_recharge_amount'].' '.$currencyUnit);
        }
        if (isset($config['max_recharge_amount']) && $amount > ($config['max_recharge_amount'] * 100)) {
            $currencyService = app(CurrencyService::class);
            $currencyCode = $config['currency'] ?? $currencyService->getCurrencyCode();
            $currencyUnit = $currencyService->getCurrencyUnit($currencyCode) ?: $currencyService->getCurrencyName($currencyCode) ?: '元';
            throw new FailedException('充值金额不能超过 '.$config['max_recharge_amount'].' '.$currencyUnit);
        }

        // 使用配置中的 merchant_name 作为默认描述，如果没有则使用参数中的 subject
        $merchantName = $config['merchant_name'] ?? 'XDITN';
        $subject = $params['subject'] ?? $merchantName.' 订单支付';

        // 优先使用配置中的货币，其次使用参数中的货币，最后使用默认货币
        $currencyService = app(CurrencyService::class);
        $currency = $params['currency'] ?? $config['currency'] ?? $currencyService->getCurrencyCode();

        // 获取支付方式（card 或 paypal），默认为 card
        $paymentMethod = $params['payment_method'] ?? 'card';

        // 获取支付 URL 配置，优先使用配置中的值
        $returnUrl = $params['return_url'] ?? $config['return_url'] ?? '';
        $cancelUrl = $params['cancel_url'] ?? $config['cancel_url'] ?? '';
        $notifyUrl = $params['notify_url'] ?? $config['notify_url'] ?? route('pay.callback', ['gateway' => 'airwallex']);

        try {
            $baseUrl = $this->getBaseUrl();
            $accessToken = $this->getAccessToken();

            // 构建支付意图请求
            $requestData = [
                'request_id' => uniqid('req_', true),
                'merchant_order_id' => $orderNo,
                'amount' => number_format($amount / 100, 2, '.', ''),
                'currency' => $currency,
                'descriptor' => $subject,
                'customer' => [
                    'email' => $params['customer_email'] ?? '',
                ],
            ];

            // 如果指定了 PayPal 支付方式，添加 payment_method 参数
            if ($paymentMethod === 'paypal') {
                if (empty($returnUrl) || empty($cancelUrl)) {
                    throw new FailedException('PayPal 支付需要提供 return_url 和 cancel_url');
                }

                $requestData['payment_method'] = [
                    'type' => 'paypal',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ];
            }
            // Card 支付：不包含 payment_method，让前端使用 Airwallex Elements 收集卡信息
            // 如果包含 payment_method.type = 'card'，API 会要求提供 payment_method.card 对象
            // 但卡信息应该在前端收集，所以不在这里指定

            // 创建支付意图
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl.'/api/v1/pa/payment_intents/create', $requestData);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = $response->json();
                Log::channel('payment')->error('Airwallex 创建支付意图失败', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'error_data' => $errorData,
                    'order_no' => $orderNo,
                    'request_data' => $requestData,
                    'base_url' => $baseUrl,
                ]);

                // 如果是权限错误，提供更详细的提示
                if (isset($errorData['code']) && $errorData['code'] === 'unauthorized') {
                    throw new FailedException('Airwallex API 权限不足，请检查 API Key 是否具有创建支付意图的权限。错误详情：'.$errorBody);
                }

                throw new FailedException('Airwallex 创建支付意图失败: '.$errorBody);
            }

            $data = $response->json();
            $paymentIntentId = $data['id'] ?? '';
            $clientSecret = $data['client_secret'] ?? '';

            if (empty($paymentIntentId)) {
                throw new FailedException('Airwallex 创建支付意图失败：未获取到支付意图 ID');
            }

            // 根据支付方式处理不同的返回结果
            if ($paymentMethod === 'paypal') {
                // PayPal 支付：需要调用 confirm API 获取授权 URL
                $confirmResponse = Http::withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post($baseUrl.'/api/v1/pa/payment_intents/'.$paymentIntentId.'/confirm', [
                    'request_id' => uniqid('req_', true),
                ]);

                if (!$confirmResponse->successful()) {
                    $errorBody = $confirmResponse->body();
                    Log::channel('payment')->error('Airwallex 确认 PayPal 支付意图失败', [
                        'status' => $confirmResponse->status(),
                        'body' => $errorBody,
                        'payment_intent_id' => $paymentIntentId,
                    ]);
                    throw new FailedException('Airwallex 确认 PayPal 支付意图失败: '.$errorBody);
                }

                $confirmData = $confirmResponse->json();
                // PayPal 授权 URL 在 next_action.redirect_to_url.url 中
                $paymentUrl = $confirmData['next_action']['redirect_to_url']['url'] ?? '';

                if (empty($paymentUrl)) {
                    throw new FailedException('Airwallex 确认 PayPal 支付意图失败：未获取到授权 URL');
                }

                return [
                    'order_no' => $orderNo,
                    'out_trade_no' => $paymentIntentId,
                    'approval_url' => $paymentUrl,
                    'pay_url' => $paymentUrl,
                    'payment_intent_id' => $paymentIntentId,
                    'client_secret' => $clientSecret, // 也返回 client_secret（虽然 PayPal 可能不需要）
                ];
            } else {
                // Card 支付：返回客户端确认 URL 和 client_secret（前端使用 Airwallex Elements 收集卡信息）
                $paymentUrl = $returnUrl.'?payment_intent_id='.$paymentIntentId;

                return [
                    'order_no' => $orderNo,
                    'out_trade_no' => $paymentIntentId,
                    'approval_url' => $paymentUrl,
                    'pay_url' => $paymentUrl,
                    'payment_intent_id' => $paymentIntentId,
                    'client_secret' => $clientSecret, // 前端使用 Airwallex Elements 时需要
                ];
            }
        } catch (\Exception $e) {
            Log::channel('payment')->error('Airwallex 创建支付订单失败', [
                'error' => $e->getMessage(),
                'order_no' => $orderNo,
            ]);

            throw $e;
        }
    }

    /**
     * 查询订单.
     */
    public function query(array $params): array
    {
        $paymentIntentId = $params['out_trade_no'] ?? '';

        if (empty($paymentIntentId)) {
            return [];
        }

        try {
            $baseUrl = $this->getBaseUrl();
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->get($baseUrl.'/api/v1/pa/payment_intents/'.$paymentIntentId);

            if (!$response->successful()) {
                Log::channel('payment')->error('Airwallex 查询订单失败', [
                    'status' => $response->status(),
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return [];
            }

            $data = $response->json();

            return [
                'id' => $data['id'] ?? '',
                'status' => $data['status'] ?? '',
                'amount' => $data['amount'] ?? 0,
                'currency' => $data['currency'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::channel('payment')->error('Airwallex 查询订单失败', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            return [];
        }
    }

    /**
     * 退款.
     */
    public function refund(array $params): mixed
    {
        $paymentIntentId = $params['transaction_id'] ?? $params['out_trade_no'] ?? '';
        $amount = $params['amount'] ?? 0;
        $reason = $params['reason'] ?? '退款';
        $currencyService = app(CurrencyService::class);
        $currency = $params['currency'] ?? $currencyService->getCurrencyCode();

        if (empty($paymentIntentId)) {
            throw new FailedException('Airwallex 退款失败：缺少交易 ID');
        }

        try {
            $baseUrl = $this->getBaseUrl();
            $accessToken = $this->getAccessToken();

            // 创建退款请求
            $requestData = [
                'request_id' => uniqid('req_', true),
                'amount' => number_format($amount / 100, 2, '.', ''),
                'currency' => $currency,
                'reason' => $reason,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl.'/api/v1/pa/payment_intents/'.$paymentIntentId.'/refund', $requestData);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::channel('payment')->error('Airwallex 退款失败', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                throw new FailedException('Airwallex 退款失败: '.$errorBody);
            }

            $data = $response->json();
            $refundId = $data['id'] ?? '';
            $status = $data['status'] ?? '';

            $result = [
                'refund_id' => $refundId,
                'status' => strtolower($status),
                'out_trade_no' => $paymentIntentId,
            ];

            return $result;
        } catch (\Exception $e) {
            Log::channel('payment')->error('Airwallex 退款失败', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw $e;
        }
    }

    /**
     * 处理 Airwallex Webhook 回调.
     */
    public function notify(): mixed
    {
        $request = request();
        $webhookData = $request->all();

        // 如果没有数据，尝试从 JSON 获取
        if (empty($webhookData)) {
            $webhookData = $request->json()->all();
        }

        // 验证 Webhook 签名
        $this->verifyWebhookSignature($request);

        // 处理 Webhook 数据
        $notify = $this->getNotifyData($webhookData);

        try {
            \Illuminate\Support\Facades\Event::dispatch(new \Modules\Pay\Events\PayNotifyEvent($notify));
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Airwallex Webhook 处理失败', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Airwallex Webhook 需要返回 200 状态码和 JSON 响应
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * 验证 Airwallex Webhook 签名.
     *
     * Airwallex 使用 HMAC-SHA256 算法对 Webhook 请求进行签名
     * 签名密钥在 Webhook 配置页面获取（whsec_xxx）
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     *
     * @throws FailedException
     */
    protected function verifyWebhookSignature($request): void
    {
        $config = config('pay.airwallex', []);
        $webhookSecret = $config['webhook_secret'] ?? null;

        // 如果未配置 webhook_secret，跳过验证（不推荐，但允许开发环境）
        if (empty($webhookSecret)) {
            Log::channel('payment')->warning('Airwallex Webhook 签名验证已跳过：未配置 webhook_secret');

            return;
        }

        // 获取签名头
        $signature = $request->header('x-airwallex-signature');
        if (empty($signature)) {
            Log::channel('payment')->error('Airwallex Webhook 签名验证失败：缺少签名头');
            throw new FailedException('Airwallex Webhook 签名验证失败：缺少签名头');
        }

        // 获取请求体
        $payload = $request->getContent();
        if (empty($payload)) {
            Log::channel('payment')->error('Airwallex Webhook 签名验证失败：请求体为空');
            throw new FailedException('Airwallex Webhook 签名验证失败：请求体为空');
        }

        // 计算期望的签名
        // Airwallex 使用 HMAC-SHA256 算法
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // 验证签名
        // Airwallex 的签名格式可能是 "t=timestamp,v1=signature" 或直接是签名
        // 这里简化处理，直接比较签名
        if (!hash_equals($expectedSignature, $signature)) {
            // 尝试解析签名格式 "t=timestamp,v1=signature"
            $signatureParts = explode(',', $signature);
            $actualSignature = null;
            foreach ($signatureParts as $part) {
                if (strpos($part, 'v1=') === 0) {
                    $actualSignature = substr($part, 3);
                    break;
                }
            }

            // 如果解析出签名，再次验证
            if ($actualSignature && hash_equals($expectedSignature, $actualSignature)) {
                return;
            }

            Log::channel('payment')->error('Airwallex Webhook 签名验证失败', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
            throw new FailedException('Airwallex Webhook 签名验证失败');
        }
    }

    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new AirwallexNotifyData($data);
    }

    /**
     * 订单号前缀
     */
    protected function orderNoPrefix(): string
    {
        return 'AW';
    }

    /**
     * 支付平台.
     */
    protected function platform(): PayPlatform
    {
        return PayPlatform::AIRWALLEX;
    }
}
