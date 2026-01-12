<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use App\Services\CurrencyService;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\NotifyData;
use Modules\Pay\Support\NotifyData\PayPalNotifyData;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Controllers\PaymentsController;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Models\Builders\MoneyBuilder;
use PaypalServerSdkLib\Models\Builders\OrderApplicationContextBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\RefundRequestBuilder;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\OrderApplicationContextLandingPage;
use PaypalServerSdkLib\Models\OrderApplicationContextUserAction;
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

/**
 * PayPal支付类.
 *
 * 使用 PayPal 官方 PHP SDK (paypal/paypal-server-sdk) 实现支付功能
 * 注意：yansongda/pay 不支持 PayPal，因此使用官方 SDK
 */
class PayPal extends Pay
{
    protected ?PaypalServerSdkClient $client = null;

    public function __construct(
        protected readonly CurrencyService $currencyService
    ) {
    }

    /**
     * 加载支付配置并初始化 PayPal 官方 SDK 客户端.
     */
    protected function loadClient(): void
    {
        if ($this->client === null) {
            $config = config('pay.paypal', []);

            if (empty($config['client_id']) || empty($config['client_secret'])) {
                throw new \Exception('PayPal 配置不完整，请检查 config/pay.php 中的 paypal 配置。client_id 和 client_secret 不能为空。');
            }

            // 处理 mode：支持字符串 'sandbox'/'live' 或数字 0/1
            $mode = $config['mode'] ?? 'sandbox';
            if (is_string($mode)) {
                $mode = strtolower($mode);
                $environment = ($mode === 'live' || $mode === 'production') ? Environment::PRODUCTION : Environment::SANDBOX;
            } else {
                $environment = ($mode === 1) ? Environment::PRODUCTION : Environment::SANDBOX;
            }

            try {
                $this->client = PaypalServerSdkClientBuilder::init()
                    ->clientCredentialsAuthCredentials(
                        ClientCredentialsAuthCredentialsBuilder::init(
                            $config['client_id'],
                            $config['client_secret']
                        )
                    )
                    ->environment($environment)
                    ->build();
            } catch (\Throwable $e) {
                throw new \Exception('PayPal 客户端初始化失败: '.$e->getMessage());
            }
        }
    }

    /**
     * 获取 Orders 控制器.
     */
    public function getOrdersController(): OrdersController
    {
        $this->loadClient();

        return $this->client->getOrdersController();
    }

    /**
     * 获取 Payments 控制器.
     */
    protected function getPaymentsController(): PaymentsController
    {
        $this->loadClient();

        return $this->client->getPaymentsController();
    }

    /**
     * 获取PayPal客户端实例.
     *
     * 注意：为了保持与 yansongda/pay 架构的一致性，
     * 这里返回一个包装对象，但实际上 PayPal 使用官方 SDK
     */
    protected function instance(): mixed
    {
        $this->loadClient();

        // 返回一个包装对象，实现必要的接口方法以兼容父类
        return new class($this) {
            public function __construct(
                protected PayPal $paypal
            ) {
            }

            /**
             * PayPal 使用 Webhook，不通过 callback() 方法
             * 这里返回一个空对象以兼容接口.
             */
            public function callback(): mixed
            {
                return new class() {
                    public function toArray(): array
                    {
                        return [];
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
        $orderNo = $params['order_no'] ?? $this->createOrderNo();
        $amount = $params['total_amount'] ?? $params['amount'] ?? 0;
        $subject = $params['subject'] ?? '订单支付';
        $currency = $params['currency'] ?? $this->currencyService->getCurrencyCode();

        // 获取支付 URL 配置
        $returnUrl = $params['return_url'] ?? config('pay.paypal.return_url', '');
        $cancelUrl = $params['cancel_url'] ?? config('pay.paypal.cancel_url', $returnUrl);
        $notifyUrl = $params['notify_url'] ?? config('pay.paypal.notify_url', route('pay.callback', ['gateway' => 'paypal']));

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
            // 使用 PayPal 官方 SDK 构建订单请求
            $orderRequest = OrderRequestBuilder::init(
                CheckoutPaymentIntent::CAPTURE,
                [
                    PurchaseUnitRequestBuilder::init(
                        AmountWithBreakdownBuilder::init(
                            $currency,
                            number_format($amount / 100, 2, '.', '') // PayPal 使用元为单位
                        )->build()
                    )
                        ->referenceId($orderNo)
                        ->description($subject)
                        ->customId($orderNo)
                        ->build(),
                ]
            )
                ->applicationContext(
                    OrderApplicationContextBuilder::init()
                        ->brandName(config('app.name', 'PayPal Payment'))
                        ->landingPage(OrderApplicationContextLandingPage::BILLING)
                        ->userAction(OrderApplicationContextUserAction::PAY_NOW)
                        ->returnUrl($returnUrl)
                        ->cancelUrl($cancelUrl)
                        ->build()
                )
                ->build();

            // 调用 PayPal API 创建订单
            $ordersController = $this->getOrdersController();
            $apiResponse = $ordersController->createOrder([
                'body' => $orderRequest,
                'prefer' => 'return=representation',
            ]);

            $order = $apiResponse->getResult();

            // 提取审批链接
            $approvalUrl = '';
            if ($order && method_exists($order, 'getLinks')) {
                $links = $order->getLinks();
                foreach ($links as $link) {
                    if (method_exists($link, 'getRel') && $link->getRel() === 'approve') {
                        $approvalUrl = method_exists($link, 'getHref') ? $link->getHref() : '';
                        break;
                    }
                }
            }

            $orderId = $order && method_exists($order, 'getId') ? $order->getId() : '';
            // 注意：事件在控制器层触发，不在支付类中触发

            return [
                'order_no' => $orderNo,
                'out_trade_no' => $orderId,
                'approval_url' => $approvalUrl,
                'pay_url' => $approvalUrl,
            ];
        } catch (\Throwable $e) {
            // 注意：事件在控制器层触发，不在支付类中触发
            throw $e;
        }
    }

    /**
     * 查询订单.
     */
    public function query(array $params): array
    {
        $orderId = $params['out_trade_no'] ?? '';

        if (empty($orderId)) {
            return [];
        }

        try {
            $ordersController = $this->getOrdersController();
            $apiResponse = $ordersController->getOrder([
                'id' => $orderId,
            ]);

            $order = $apiResponse->getResult();

            // 转换为数组格式
            $result = [];
            if ($order) {
                // 处理对象或数组两种情况
                if (is_object($order)) {
                    $result = [
                        'id' => method_exists($order, 'getId') ? $order->getId() : '',
                        'status' => method_exists($order, 'getStatus') ? $order->getStatus() : '',
                        'intent' => method_exists($order, 'getIntent') ? $order->getIntent() : '',
                    ];
                } elseif (is_array($order)) {
                    $result = [
                        'id' => $order['id'] ?? '',
                        'status' => $order['status'] ?? '',
                        'intent' => $order['intent'] ?? '',
                    ];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * 退款.
     */
    public function refund(array $params): mixed
    {
        $captureId = $params['transaction_id'] ?? $params['out_trade_no'] ?? '';
        $amount = $params['amount'] ?? 0;
        $reason = $params['reason'] ?? '退款';
        // 如果没有传入货币，使用系统配置的货币
        $currency = $params['currency'] ?? $this->currencyService->getCurrencyCode();

        $requestParams = [
            'capture_id' => $captureId,
            'amount' => $amount,
            'currency' => $currency,
            'reason' => $reason,
        ];

        try {
            // 使用 PayPal 官方 SDK 构建退款请求
            $refundRequest = RefundRequestBuilder::init()
                ->amount(
                    MoneyBuilder::init(
                        $currency,
                        number_format($amount / 100, 2, '.', '')
                    )->build()
                )
                ->noteToPayer($reason)
                ->build();

            // 调用 PayPal API 处理退款
            $paymentsController = $this->getPaymentsController();
            $apiResponse = $paymentsController->refundCapturedPayment([
                'captureId' => $captureId,
                'body' => $refundRequest,
            ]);

            $refund = $apiResponse->getResult();

            $refundId = $refund && method_exists($refund, 'getId') ? $refund->getId() : '';
            $status = $refund && method_exists($refund, 'getStatus') ? $refund->getStatus() : '';

            $result = [
                'refund_id' => $refundId,
                'status' => strtolower($status),
                'out_trade_no' => $captureId,
            ];

            // 注意：事件在控制器层触发，不在支付类中触发

            return $result;
        } catch (\Throwable $e) {
            // 注意：事件在控制器层触发，不在支付类中触发
            throw $e;
        }
    }

    /**
     * 处理 PayPal Webhook 回调.
     *
     * PayPal 使用 Webhook 而不是同步回调，需要特殊处理
     * 重写父类的 notify() 方法以支持 PayPal Webhook
     */
    public function notify(): mixed
    {
        $this->loadClient();

        // PayPal Webhook 数据在请求体中
        $request = request();
        $webhookData = $request->all();

        // 如果没有数据，尝试从 JSON 获取
        if (empty($webhookData)) {
            $webhookData = $request->json()->all();
        }

        $webhookId = config('pay.paypal.webhook_id', '');
        $eventType = $webhookData['event_type'] ?? '';
        $resourceType = $webhookData['resource_type'] ?? '';

        // 处理 Webhook 事件
        $notify = $this->getNotifyData($webhookData);

        try {
            \Illuminate\Support\Facades\Event::dispatch(new \Modules\Pay\Events\PayNotifyEvent($notify));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('payment')->error('PayPal Webhook 触发事件失败', [
                'platform' => 'paypal',
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // PayPal Webhook 需要返回 200 状态码和 JSON 响应
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * 获取回调数据.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        return new PayPalNotifyData($data);
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
        return PayPlatform::PAYPAL;
    }

    /**
     * 创建订阅.
     *
     * 注意：PayPal 订阅功能较复杂，需要先创建产品和计划
     * 这里提供基础实现，可根据实际需求扩展
     */
    public function createSubscription(array $params): array
    {
        // TODO: 实现 PayPal 订阅创建
        // PayPal 订阅需要：
        // 1. 创建产品 (Product)
        // 2. 创建订阅计划 (Plan)
        // 3. 创建订阅 (Subscription)

        return [
            'subscription_id' => 'SUB_'.time(),
            'approval_url' => '',
        ];
    }

    /**
     * 取消订阅.
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        // TODO: 实现 PayPal 订阅取消
        return true;
    }

    /**
     * 获取订阅详情.
     */
    public function getSubscription(string $subscriptionId): array
    {
        // TODO: 实现获取 PayPal 订阅详情
        return [];
    }
}
