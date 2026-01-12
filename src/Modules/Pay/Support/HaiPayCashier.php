<?php

declare(strict_types=1);

namespace Modules\Pay\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Support\NotifyData\HaiPayNotifyData;
use Modules\Pay\Support\NotifyData\NotifyData;
use XditnModule\Exceptions\FailedException;

/**
 * HaiPay 全球收银台代收.
 *
 * 文档：docs/HaiPay全球收银台.md
 * 主要接口：
 *  - 下单：/global/cashier/collect/apply
 *  - 查询：/global/cashier/collect/query(/v2)
 */
class HaiPayCashier extends Pay
{
    /**
     * 简单包装，提供 callback()->toArray() 与 success().
     */
    protected function instance(): mixed
    {
        return new class() {
            public function callback(): mixed
            {
                return new class() {
                    public function toArray(): array
                    {
                        $request = request();
                        $data = $request->all();
                        if (empty($data)) {
                            $data = $request->json()->all();
                        }

                        return $data ?? [];
                    }
                };
            }

            public function success(): string
            {
                return 'SUCCESS';
            }
        };
    }

    /**
     * 下单：/global/cashier/collect/apply.
     *
     * 必要参数：
     * - order_no (本地订单号) -> orderId
     * - amount （分）-> 转为字符串金额（两位小数）
     * - currency
     * - name, email, region, partnerUserId, subject
     * - return_url (callBackUrl) / cancel_url (callBackFailUrl)
     * - notify_url 可覆盖配置
     */
    public function create(array $params): array
    {
        $config = $this->getConfig();
        $baseUrl = rtrim($config['base_url'], '/');

        $orderId = $params['order_no'] ?? null;
        $amountCents = $params['amount'] ?? $params['total_amount'] ?? null;
        $currency = $params['currency'] ?? $config['currency'] ?? 'USD';
        $name = $params['name'] ?? null;
        $email = $params['email'] ?? null;
        $region = $params['region'] ?? null;
        $partnerUserId = $params['partner_user_id'] ?? $params['user_id'] ?? null;
        $subject = $params['subject'] ?? 'Payment';
        $body = $params['body'] ?? null;

        if (!$orderId || $amountCents === null || !$name || !$email || !$region || !$partnerUserId) {
            throw new FailedException('HaiPay Cashier 下单缺少必要参数（order_no/amount/name/email/region/partnerUserId）');
        }

        // 金额（分转元，保留两位小数）
        $amountStr = number_format(((float) $amountCents) / 100, 2, '.', '');

        // 获取回调 URL（优先级：参数 > 配置 > 默认路由）
        $notifyUrl = $params['notify_url']
            ?? $config['notify_url']
            ?: route('pay.callback', ['gateway' => 'haipay']); // 生成绝对 URL
        // 构建请求参数（确保所有字段都有值，空字符串会被签名方法跳过）
        $payload = [
            'appId' => $config['app_id'],
            'orderId' => $orderId,
            'name' => $name,
            'email' => $email,
            'amount' => $amountStr,
            'currency' => $currency,
            'callBackUrl' => $params['return_url'] ?? $config['callback_url'] ?? '',
            'callBackFailUrl' => $params['cancel_url'] ?? $config['callback_fail_url'] ?? ($config['callback_url'] ?? ''),
            'notifyUrl' => $notifyUrl,
            'subject' => $subject,
            'region' => $region,
            'partnerUserId' => $partnerUserId,
        ];

        // body 字段可选，如果有值才添加
        if (!empty($body)) {
            $payload['body'] = $body;
        }

        // 计算签名
        // merchant_secret_key 用于构建签名字符串，secret_key 是 RSA 私钥用于签名
        // 注意：如果配置中没有 merchant_secret_key，说明 secret_key 既是商户密钥也是 RSA 私钥
        // 但根据 HaiPay 文档，应该有两个密钥：merchant_secret_key（简单字符串）和 rsa_private_key（RSA 私钥）
        $merchantSecretKey = $config['merchant_secret_key'] ?? $config['secret_key'];
        $rsaPrivateKey = $config['secret_key'];

        $payload['sign'] = $this->sign($payload, $merchantSecretKey, $rsaPrivateKey);

        $url = $baseUrl.'/global/cashier/collect/apply';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(30)
            ->acceptJson()
            ->post($url, $payload);

        $responseBody = $response->body();
        $responseData = $response->json();

        // 检查 HTTP 状态码
        if (!$response->successful()) {
            $errorMessage = $responseData['message'] ?? $responseData['msg'] ?? $responseBody;
            throw new FailedException('HaiPay Cashier 下单失败 (HTTP '.$response->status().'): '.$errorMessage);
        }

        // 检查业务状态码（HaiPay API 可能返回 HTTP 200，但业务状态码表示失败）
        // status: "1" 或 "200" 表示成功，"0" 表示失败
        $businessStatus = $responseData['status'] ?? $responseData['code'] ?? null;
        if ($businessStatus !== null) {
            // 将状态码转换为字符串进行比较（支持 "0", "1", "200" 等格式）
            $businessStatusStr = (string) $businessStatus;

            // 如果状态码是 "0" 或表示失败的值，抛出错误
            if ($businessStatusStr === '0' || $businessStatusStr === 'false') {
                $errorCode = $responseData['error'] ?? $responseData['errorCode'] ?? '';
                $errorMessage = $responseData['msg'] ?? $responseData['message'] ?? $responseData['errorMsg'] ?? '未知错误';

                Log::channel('payment')->error('HaiPay Cashier 业务状态失败', [
                    'business_status' => $businessStatusStr,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'response_data' => $responseData,
                ]);

                throw new FailedException(
                    'HaiPay Cashier 下单失败: '.$errorMessage.
                    ($errorCode ? ' (错误代码: '.$errorCode.')' : '')
                );
            }
        }

        // 尝试多种可能的响应结构
        // 1. 标准结构：{ "code": 200, "data": { "payUrl": "...", "orderNo": "..." } }
        // 2. 直接结构：{ "payUrl": "...", "orderNo": "..." }
        // 3. 下划线命名：{ "data": { "pay_url": "...", "order_no": "..." } }
        $respData = $responseData['data'] ?? $responseData;

        // 尝试多种字段名（支持驼峰和下划线命名）
        $payUrl = $respData['payUrl']
            ?? $respData['pay_url']
            ?? $respData['paymentUrl']
            ?? $respData['payment_url']
            ?? $respData['url']
            ?? '';

        $orderNo = $respData['orderNo']
            ?? $respData['order_no']
            ?? $respData['outTradeNo']
            ?? $respData['out_trade_no']
            ?? $respData['tradeNo']
            ?? $respData['trade_no']
            ?? '';

        // 如果仍然缺少必要字段，记录详细错误信息
        if (empty($payUrl) || empty($orderNo)) {
            Log::channel('payment')->error('HaiPay Cashier 响应解析失败', [
                'response_data' => $responseData,
                'resp_data' => $respData,
                'found_payUrl' => !empty($payUrl),
                'found_orderNo' => !empty($orderNo),
                'available_keys' => array_keys($respData ?? []),
            ]);

            $missingFields = [];
            if (empty($payUrl)) {
                $missingFields[] = 'payUrl/pay_url';
            }
            if (empty($orderNo)) {
                $missingFields[] = 'orderNo/order_no';
            }

            throw new FailedException(
                'HaiPay Cashier 下单响应缺少必要字段: '.implode(', ', $missingFields).'. '.
                '响应数据: '.json_encode($responseData, JSON_UNESCAPED_UNICODE)
            );
        }

        return [
            'order_no' => $orderId,
            'out_trade_no' => $orderNo,
            'pay_url' => $payUrl,
        ];
    }

    /**
     * 查询：/global/cashier/collect/query.
     */
    public function query(array $params): array
    {
        $config = $this->getConfig();
        $baseUrl = rtrim($config['base_url'], '/');

        $orderId = $params['order_no'] ?? $params['orderId'] ?? null;
        $orderNo = $params['out_trade_no'] ?? $params['orderNo'] ?? null;

        if (!$orderId || !$orderNo) {
            throw new FailedException('HaiPay Cashier 查询缺少 orderId/orderNo');
        }

        $payload = [
            'appId' => $config['app_id'],
            'orderId' => $orderId,
            'orderNo' => $orderNo,
        ];
        $payload['sign'] = $this->sign($payload, $config['merchant_secret_key'] ?? $config['secret_key'], $config['secret_key']);

        $url = $baseUrl.'/global/cashier/collect/query';
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(15)->acceptJson()->post($url, $payload);
        if (!$response->successful()) {
            throw new FailedException('HaiPay Cashier 查询失败: '.$response->body());
        }

        $data = $response->json();

        return $data['data'] ?? [];
    }

    /**
     * 收银台目前暂不支持在本系统内直接发起退款.
     */
    public function refund(array $params): mixed
    {
        throw new FailedException('HaiPay Cashier 暂未实现退款接口');
    }

    /**
     * 验证回调签名并封装 NotifyData.
     */
    protected function getNotifyData(array $data): NotifyData
    {
        // verifySignature() 方法会根据回调的 appId 或 currency 自动获取对应的配置
        if (!$this->verifySignature($data)) {
            Log::channel('payment')->warning('HaiPayCashier 回调验签失败', ['payload' => $data]);
            throw new FailedException('HaiPay Cashier 验签失败');
        }

        return new HaiPayNotifyData($data);
    }

    protected function orderNoPrefix(): string
    {
        return 'HPC';
    }

    protected function platform(): PayPlatform
    {
        return PayPlatform::HAIPAY;
    }

    /**
     * 读取配置.
     */
    protected function getConfig(): array
    {
        $config = config('pay.haipay_cashier', []);
        if (empty($config['app_id']) || empty($config['secret_key']) || empty($config['base_url'])) {
            throw new FailedException('HaiPay Cashier 配置不完整(app_id/secret_key/base_url)');
        }

        // 如果配置中有 merchant_secret_key，使用它；否则使用 secret_key 作为商户密钥
        // secret_key 是 RSA 私钥，merchant_secret_key 是用于构建签名字符串的简单字符串密钥
        if (empty($config['merchant_secret_key'])) {
            // 如果没有单独的 merchant_secret_key，可能需要从 secret_key 中提取
            // 或者 secret_key 本身就是商户密钥（不是 RSA 私钥）
            // 这里先假设 secret_key 是 RSA 私钥，需要单独的 merchant_secret_key
            // 如果 HaiPay 只需要一个密钥，则 merchant_secret_key 和 rsa_private_key 是同一个
            $config['merchant_secret_key'] = $config['secret_key'];
        }

        return $config;
    }

    /**
     * 签名：按 key 排序，拼接 k=v&...&key=merchantSecretKey，然后使用 RSA-SHA256 签名.
     *
     * HaiPay 签名规则（SHA256WithRSA）：
     * 1. 将所有非空参数的 key 按照 ASCII 排序
     * 2. 取 key（不包含 sign）和 value 进行拼接：k1=v1&k2=v2&...
     * 3. 在结尾再拼接 &key=merchantSecretKey（加密字段，后台获取）
     * 4. 采用 RSA 算法对字符串计算，算出签名字符串
     *
     * 注意：
     * - 跳过 null 值和空字符串（但数字 0 不应该被跳过）
     * - 所有值都转换为字符串
     * - 使用 UTF-8 编码
     * - 密钥长度：2048 位
     *
     * @param array $data 要签名的数据
     * @param string $merchantSecretKey 商户密钥（用于构建签名字符串的简单字符串，从 HaiPay 后台获取）
     * @param string $rsaPrivateKey RSA 私钥（用于签名的 RSA 私钥，2048 位）
     *
     * @return string Base64 编码的签名结果
     */
    protected function sign(array $data, string $merchantSecretKey, string $rsaPrivateKey): string
    {
        // 1. 移除 sign 字段（不参与签名）
        unset($data['sign']);

        // 2. 按照 ASCII 排序（ksort 按 key 的 ASCII 值排序）
        ksort($data);

        // 3. 构建签名字符串：k1=v1&k2=v2&...
        $signString = '';
        foreach ($data as $k => $v) {
            // 跳过 null 值和空字符串（但数字 0 不应该被跳过）
            // PHP 中：0 == '' 是 true，但 0 === '' 是 false，所以使用严格比较
            if ($v === null || $v === '') {
                continue;
            }
            // 确保值为字符串类型，并使用 UTF-8 编码
            $value = (string) $v;
            // 确保字符串是 UTF-8 编码（如果不是，转换为 UTF-8）
            if (!mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value));
            }
            $signString .= $k.'='.$value.'&';
        }

        // 4. 在结尾拼接 &key=merchantSecretKey
        $signString .= 'key='.$merchantSecretKey;

        // 确保签名字符串是 UTF-8 编码
        if (!mb_check_encoding($signString, 'UTF-8')) {
            $signString = mb_convert_encoding($signString, 'UTF-8', mb_detect_encoding($signString));
        }        // 5. 使用 RSA-SHA256 签名（使用 RSA 私钥对签名字符串进行签名）

        return $this->buildRSASignByPrivateKey($signString, $rsaPrivateKey);
    }

    /**
     * 使用 RSA 私钥进行 SHA256 签名.
     *
     * 与 HaiPay 官方工具 SHA256WithRSAUtils::buildRSASignByPrivateKey() 保持一致
     *
     * @param string $data 要签名的数据
     * @param string $privateKey RSA 私钥（Base64 编码，不包含头尾标记，或完整的 PEM 格式）
     *
     * @return string Base64 编码的签名结果
     */
    protected function buildRSASignByPrivateKey(string $data, string $privateKey): string
    {
        // 获取私钥资源（与 HaiPay 官方工具保持一致）
        $privateKeyResource = openssl_get_privatekey($this->getPrivateKey($privateKey));

        if (!$privateKeyResource) {
            $error = openssl_error_string();
            throw new FailedException('HaiPay Cashier RSA 私钥格式错误，无法加载私钥: '.($error ?: '未知错误'));
        }

        // 使用 SHA256 算法进行签名（与 HaiPay 官方工具保持一致：OPENSSL_ALGO_SHA256）
        $signature = '';
        $result = openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (!$result) {
            $error = openssl_error_string();
            throw new FailedException('HaiPay Cashier RSA 签名失败: '.($error ?: '未知错误'));
        }

        // 返回 Base64 编码的签名
        return base64_encode($signature);
    }

    /**
     * 获取完整的 RSA 私钥（PEM 格式字符串）.
     *
     * 与 HaiPay 官方工具 SHA256WithRSAUtils::getPrivateKey() 保持一致
     * 返回 PEM 格式字符串，而不是 OpenSSL 资源
     *
     * 支持两种格式：
     * 1. 完整的 PEM 格式（包含 BEGIN/END 标记）- 直接返回
     * 2. Base64 编码的内容（不包含头尾标记）- 转换为 PKCS#1 格式
     *
     * HaiPay 使用 2048 位 RSA 密钥，默认使用 PKCS#1 格式
     *
     * @param string $privateKey 私钥内容（Base64 编码，不包含头尾标记，或完整的 PEM 格式）
     *
     * @return string PEM 格式的私钥字符串
     */
    protected function getPrivateKey(string $privateKey): string
    {
        // 如果已经是完整的 PEM 格式（包含 BEGIN/END 标记），直接返回
        if (str_contains($privateKey, 'BEGIN') && str_contains($privateKey, 'END')) {
            return $privateKey;
        }

        // 否则，构建完整的 PEM 格式（PKCS#1 格式，与 HaiPay 官方工具保持一致）
        // 移除可能的换行符和空格
        $privateKey = preg_replace('/\s+/', '', $privateKey);

        // 构建 PKCS#1 格式（-----BEGIN RSA PRIVATE KEY-----）
        $pem = '-----BEGIN RSA PRIVATE KEY-----'.PHP_EOL;
        $pem .= chunk_split($privateKey, 64, PHP_EOL);
        $pem .= '-----END RSA PRIVATE KEY-----'.PHP_EOL;

        return $pem;
    }

    /**
     * 获取签名计算的调试信息（用于排查签名问题）.
     */
    protected function getSignDebugInfo(array $data, string $secret): array
    {
        unset($data['sign']);
        ksort($data);
        $signString = '';
        $parts = [];
        foreach ($data as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $part = $k.'='.$v;
            $signString .= $part.'&';
            $parts[] = $part;
        }
        $signString .= 'key='.$secret;

        return [
            'sign_string' => $signString,
            'parts' => $parts,
            'secret_length' => strlen($secret),
        ];
    }

    /**
     * 验证回调签名.
     *
     * HaiPay 回调签名验证规则：
     * 1. 构建签名字符串（排除 sign 和 s 字段）
     * 2. 使用 RSA 公钥验证签名（HaiPay 使用 RSA 私钥签名，我们使用 RSA 公钥验证）
     *
     * 所有币种使用相同的公钥和加密字段（merchant_secret_key）
     *
     * @param array $data 回调数据
     *
     * @return bool 验证结果
     */
    public function verifySignature(array $data): bool
    {
        if (empty($data['sign'])) {
            return false;
        }

        $sign = (string) $data['sign'];
        $config = $this->getConfig();

        // 从配置中获取商户密钥（用于构建签名字符串）
        $merchantSecretKey = $config['merchant_secret_key'] ?? null;
        if (empty($merchantSecretKey)) {
            Log::channel('payment')->error('HaiPay Cashier 验签失败：未配置 merchant_secret_key');

            return false;
        }

        // 从配置中获取公钥（用于验证回调签名）
        $publicKey = $config['public_key'] ?? null;
        if (empty($publicKey)) {
            Log::channel('payment')->error('HaiPay Cashier 验签失败：未配置 platform_public_key 或 public_key', [
                'has_platform_public_key' => !empty($config['platform_public_key']),
                'has_public_key' => !empty($config['public_key']),
            ]);

            return false;
        }

        // 构建签名字符串（排除 sign 和 s 字段）
        $signData = $data;
        unset($signData['sign'], $signData['s']);

        // 构建签名字符串
        $signString = $this->buildSignString($signData, $merchantSecretKey);

        // 使用 HaiPay 的公钥验证签名
        $verified = $this->verifyRSASignatureByPublicKey($signString, $publicKey, $sign);

        if (!$verified) {
            Log::channel('payment')->error('HaiPay Cashier 回调验签失败', [
                'order_id' => $data['orderId'] ?? null,
            ]);
        }

        return $verified;
    }

    /**
     * 构建签名字符串（用于回调验证）.
     *
     * @param array $data 数据
     * @param string $merchantSecretKey 商户密钥
     *
     * @return string 签名字符串
     */
    protected function buildSignString(array $data, string $merchantSecretKey): string
    {
        unset($data['sign'], $data['s']);
        ksort($data);

        $signString = '';
        foreach ($data as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $value = (string) $v;
            if (!mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value));
            }
            $signString .= $k.'='.$value.'&';
        }
        $signString .= 'key='.$merchantSecretKey;

        if (!mb_check_encoding($signString, 'UTF-8')) {
            $signString = mb_convert_encoding($signString, 'UTF-8', mb_detect_encoding($signString));
        }

        return $signString;
    }

    /**
     * 使用 RSA 公钥验证签名.
     *
     * 与 HaiPay 官方工具 SHA256WithRSAUtils::buildRSAverifyByPublicKey() 保持一致
     *
     * @param string $data 原始数据（签名字符串）
     * @param string $publicKey 公钥（Base64 编码或 PEM 格式）
     * @param string $signature Base64 编码的签名
     *
     * @return bool 验证结果（true 表示验证成功，false 表示验证失败）
     */
    protected function verifyRSASignatureByPublicKey(string $data, string $publicKey, string $signature): bool
    {
        // 获取 PEM 格式的公钥字符串
        $publicKeyPem = $this->getPublicKey($publicKey);

        // 获取公钥资源（与 HaiPay 官方工具保持一致）
        $publicKeyResource = openssl_get_publickey($publicKeyPem);

        if (!$publicKeyResource) {
            $error = openssl_error_string();
            Log::channel('payment')->error('HaiPay Cashier RSA 公钥格式错误，无法加载公钥', [
                'error' => $error,
                'public_key_pem_preview' => substr($publicKeyPem, 0, 100).'...',
            ]);

            return false;
        }

        // 解码 Base64 签名
        $signatureBinary = base64_decode($signature, true);
        if ($signatureBinary === false) {
            Log::channel('payment')->error('HaiPay Cashier RSA 签名格式错误，无法解码 Base64', [
                'signature_length' => strlen($signature),
                'signature_preview' => substr($signature, 0, 50).'...',
            ]);
            openssl_free_key($publicKeyResource);

            return false;
        }

        // 使用公钥验证签名（与 HaiPay 官方工具保持一致：OPENSSL_ALGO_SHA256）
        $result = openssl_verify($data, $signatureBinary, $publicKeyResource, OPENSSL_ALGO_SHA256);        // 释放资源
        openssl_free_key($publicKeyResource);

        // openssl_verify 返回 1 表示验证成功，0 表示失败，-1 表示错误
        // 与 HaiPay 官方工具保持一致：== 1 ? true : false
        return $result === 1;
    }

    /**
     * 获取完整的 RSA 公钥（PEM 格式字符串）.
     *
     * 与 HaiPay 官方工具 SHA256WithRSAUtils::getPublicKey() 保持一致
     * 返回 PEM 格式字符串，而不是 OpenSSL 资源
     *
     * 支持两种格式：
     * 1. 完整的 PEM 格式（包含 BEGIN/END 标记）- 直接返回
     * 2. Base64 编码的内容（不包含头尾标记）- 转换为标准格式
     *
     * @param string $publicKey 公钥内容（Base64 编码，不包含头尾标记，或完整的 PEM 格式）
     *
     * @return string PEM 格式的公钥字符串
     */
    protected function getPublicKey(string $publicKey): string
    {
        // 如果已经是完整的 PEM 格式（包含 BEGIN/END 标记），直接返回
        if (str_contains($publicKey, 'BEGIN') && str_contains($publicKey, 'END')) {
            return $publicKey;
        }

        // 否则，构建完整的 PEM 格式（标准格式，与 HaiPay 官方工具保持一致）
        // 移除可能的换行符和空格
        $publicKey = preg_replace('/\s+/', '', $publicKey);

        // 构建标准格式（-----BEGIN PUBLIC KEY-----）
        $pem = '-----BEGIN PUBLIC KEY-----'.PHP_EOL;
        $pem .= chunk_split($publicKey, 64, PHP_EOL);
        $pem .= '-----END PUBLIC KEY-----'.PHP_EOL;

        return $pem;
    }
}
