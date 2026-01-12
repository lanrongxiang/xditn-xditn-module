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
 * HaiPay PayPal 代付（出款）.
 *
 * 文档：docs/HaiPay-paypal接口.md
 * 接口：
 *  - 代付申请：/{currency}/pay/apply  (例如 /usd/pay/apply)
 *  - 代付查询：/{currency}/pay/query
 */
class HaiPayPaypalPayout extends Pay
{
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
                        Log::channel('payment')->info('HaiPayPaypalPayout 回调原始数据', ['payload' => $data]);

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
     * 代付申请 /{currency}/pay/apply.
     */
    public function create(array $params): array
    {
        $config = $this->getConfig();
        $baseUrl = rtrim($config['base_url'], '/');
        $currency = strtolower($config['currency'] ?? 'usd');

        $orderId = $params['order_no'] ?? $params['orderId'] ?? null;
        $amount = $params['amount'] ?? null;
        $name = $params['name'] ?? null;
        $phone = $params['phone'] ?? null;
        $email = $params['email'] ?? null;
        $accountNo = $params['account_no'] ?? $params['accountNo'] ?? null;
        $bankCode = $params['bank_code'] ?? $params['bankCode'] ?? 'PayPal';
        $accountType = $params['account_type'] ?? $params['accountType'] ?? 'EWALLET';
        $partnerUserId = $params['partner_user_id'] ?? $params['user_id'] ?? null;
        $subject = $params['subject'] ?? 'Payout';
        $notifyUrl = $params['notify_url'] ?? $config['notify_url'] ?? '';

        if (!$orderId || $amount === null || !$name || !$phone || !$email || !$accountNo || !$partnerUserId) {
            throw new FailedException('HaiPay PayPal 代付缺少必要参数（order_no/amount/name/phone/email/accountNo/partnerUserId）');
        }

        $payload = [
            'appId' => $config['app_id'],
            'orderId' => $orderId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'notifyUrl' => $notifyUrl,
            'amount' => number_format((float) $amount, 2, '.', ''), // 单位 USD，文档要求两位小数
            'accountType' => $accountType,
            'bankCode' => $bankCode,
            'subject' => $subject,
            'accountNo' => $accountNo,
            'partnerUserId' => $partnerUserId,
        ];

        $payload['sign'] = $this->sign($payload, $config['secret_key']);

        $url = $baseUrl.'/'.$currency.'/pay/apply';
        Log::channel('payment')->info('HaiPay PayPal 代付申请', ['url' => $url, 'payload' => $payload]);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(30)->acceptJson()->post($url, $payload);
        if (!$response->successful()) {
            throw new FailedException('HaiPay PayPal 代付申请失败: '.$response->body());
        }

        $data = $response->json();
        $resp = $data['data'] ?? [];

        return [
            'order_no' => $orderId,
            'out_trade_no' => $resp['orderNo'] ?? '',
            'pay_url' => $resp['payUrl'] ?? '',
            'approval_url' => $resp['payUrl'] ?? '',
            'raw' => $resp,
        ];
    }

    /**
     * 查询 /{currency}/pay/query.
     */
    public function query(array $params): array
    {
        $config = $this->getConfig();
        $baseUrl = rtrim($config['base_url'], '/');
        $currency = strtolower($config['currency'] ?? 'usd');

        $orderId = $params['order_no'] ?? $params['orderId'] ?? null;
        $orderNo = $params['out_trade_no'] ?? $params['orderNo'] ?? null;

        if (!$orderId) {
            throw new FailedException('HaiPay PayPal 代付查询缺少 orderId');
        }

        $payload = [
            'appId' => $config['app_id'],
            'orderId' => $orderId,
        ];
        if ($orderNo) {
            $payload['orderNo'] = $orderNo;
        }
        $payload['sign'] = $this->sign($payload, $config['secret_key']);

        $url = $baseUrl.'/'.$currency.'/pay/query';
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::timeout(15)->acceptJson()->post($url, $payload);
        if (!$response->successful()) {
            throw new FailedException('HaiPay PayPal 代付查询失败: '.$response->body());
        }

        $data = $response->json();

        return $data['data'] ?? [];
    }

    protected function getNotifyData(array $data): NotifyData
    {
        $config = $this->getConfig();
        if (!$this->verifySignature($data, $config['secret_key'])) {
            Log::channel('payment')->warning('HaiPay PayPal 代付回调验签失败', ['payload' => $data]);
            throw new FailedException('HaiPay PayPal 代付验签失败');
        }

        return new HaiPayNotifyData($data);
    }

    protected function orderNoPrefix(): string
    {
        return 'HPP';
    }

    protected function platform(): PayPlatform
    {
        return PayPlatform::HAIPAY;
    }

    protected function getConfig(): array
    {
        $config = config('pay.haipay_paypal_payout', []);
        if (empty($config['app_id']) || empty($config['secret_key']) || empty($config['base_url'])) {
            throw new FailedException('HaiPay PayPal 配置不完整(app_id/secret_key/base_url)');
        }

        return $config;
    }

    /**
     * 代付退款/撤销，后续可根据 HaiPay 文档扩展.
     */
    public function refund(array $params): mixed
    {
        throw new FailedException('HaiPay PayPal 代付暂未在系统内实现退款接口');
    }

    protected function sign(array $data, string $secret): string
    {
        unset($data['sign']);
        ksort($data);
        $signString = '';
        foreach ($data as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $signString .= $k.'='.$v.'&';
        }
        $signString .= 'key='.$secret;

        return strtoupper(md5($signString));
    }

    protected function verifySignature(array $data, string $secret): bool
    {
        if (empty($data['sign'])) {
            return false;
        }
        $sign = (string) $data['sign'];
        $calc = $this->sign($data, $secret);

        return hash_equals($calc, $sign);
    }
}
