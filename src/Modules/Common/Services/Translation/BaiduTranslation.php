<?php

declare(strict_types=1);

namespace Modules\Common\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use XditnModule\Exceptions\FailedException;

/**
 * 百度翻译实现（大模型文本翻译API）
 * 参考文档：https://fanyi-api.baidu.com/doc/21.
 */
class BaiduTranslation implements TranslationInterface
{
    /**
     * 获取百度翻译配置.
     */
    protected function getConfig(): array
    {
        $config = config('translation.baidu', []);

        // 如果没有配置，尝试从旧配置读取（兼容）
        if (empty($config)) {
            $config = [
                'app_id' => config('services.baidu.translate_app_id'),
                'app_secret' => config('services.baidu.translate_app_key'),
                'secret_key' => config('services.baidu.translate_api_key'),
                'api_url' => 'https://fanyi-api.baidu.com/ait/api/aiTextTranslate',
                'enabled' => 1,
                'timeout' => 30,
                'retry_times' => 3,
            ];
        }

        return $config;
    }

    /**
     * 是否启用.
     */
    protected function isEnabled(): bool
    {
        $config = $this->getConfig();

        return (bool) ($config['enabled'] ?? 1);
    }

    /**
     * 翻译文本.
     */
    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        if (empty($text)) {
            return '';
        }

        if ($sourceLang === $targetLang) {
            return $text;
        }

        if (!$this->isEnabled()) {
            throw new FailedException('百度翻译服务未启用');
        }

        $config = $this->getConfig();
        $appId = $config['app_id'] ?? '';
        $secretKey = $config['secret_key'] ?? '';
        $appSecret = $config['app_secret'] ?? '';

        // 优先使用 secret_key（API Key 鉴权）
        if (!empty($secretKey)) {
            return $this->translateWithApiKey($text, $sourceLang, $targetLang, $appId, $secretKey, $config);
        }

        // 如果没有 secret_key，使用 app_secret（Sign 鉴权）
        if (!empty($appSecret)) {
            if (empty($appId)) {
                throw new FailedException('未配置百度翻译 API，请配置 translation.baidu.app_id 和 translation.baidu.secret_key（或 translation.baidu.app_secret）');
            }

            return $this->translateWithSign($text, $sourceLang, $targetLang, $appId, $appSecret, $config);
        }

        throw new FailedException('未配置百度翻译 API，请配置 translation.baidu.secret_key 或 translation.baidu.app_secret');
    }

    /**
     * 使用 API Key 鉴权翻译（推荐方式）.
     */
    protected function translateWithApiKey(string $text, string $sourceLang, string $targetLang, string $appId, string $apiKey, array $config): string
    {
        // 百度翻译语言代码映射
        $from = $this->mapLanguageCode($sourceLang);
        $to = $this->mapLanguageCode($targetLang);

        $timeout = (int) ($config['timeout'] ?? 30);
        $maxRetries = (int) ($config['retry_times'] ?? 3);
        $apiUrl = $config['api_url'] ?? 'https://fanyi-api.baidu.com/ait/api/aiTextTranslate';
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                $response = Http::withOptions([
                    'timeout' => $timeout,
                    'connect_timeout' => min(10, $timeout / 3),
                ])->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])->post($apiUrl, [
                    'appid' => $appId,
                    'from' => $from,
                    'to' => $to,
                    'q' => $text,
                    'model_type' => 'llm', // 使用大模型翻译
                ]);

                return $this->handleResponse($response, $retryCount, $maxRetries, $sourceLang, $targetLang);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($retryCount < $maxRetries) {
                    $retryCount++;
                    Log::warning('百度翻译连接失败，准备重试', [
                        'error' => $e->getMessage(),
                        'source' => $sourceLang,
                        'target' => $targetLang,
                        'retry_count' => $retryCount,
                    ]);
                    sleep(1);
                    continue;
                }

                throw new FailedException('百度翻译连接超时，请检查网络连接或稍后重试: '.$e->getMessage());
            } catch (\Exception $e) {
                throw new FailedException('百度翻译失败: '.$e->getMessage());
            }
        }

        throw new FailedException('百度翻译失败：已达到最大重试次数');
    }

    /**
     * 使用 Sign 鉴权翻译（兼容旧方式）.
     */
    protected function translateWithSign(string $text, string $sourceLang, string $targetLang, string $appId, string $appKey, array $config): string
    {
        // 百度翻译语言代码映射
        $from = $this->mapLanguageCode($sourceLang);
        $to = $this->mapLanguageCode($targetLang);

        $timeout = (int) ($config['timeout'] ?? 30);
        $maxRetries = (int) ($config['retry_times'] ?? 3);
        $apiUrl = $config['api_url'] ?? 'https://fanyi-api.baidu.com/ait/api/aiTextTranslate';
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                // 生成随机数（salt）
                $salt = time().rand(1000, 9999);

                // 计算签名：md5(appid + q + salt + appKey)
                $sign = md5($appId.$text.$salt.$appKey);

                $response = Http::withOptions([
                    'timeout' => $timeout,
                    'connect_timeout' => min(10, $timeout / 3),
                ])->asForm()->post($apiUrl, [
                    'q' => $text,
                    'from' => $from,
                    'to' => $to,
                    'appid' => $appId,
                    'salt' => $salt,
                    'sign' => $sign,
                    'model_type' => 'llm', // 使用大模型翻译
                ]);

                return $this->handleResponse($response, $retryCount, $maxRetries, $sourceLang, $targetLang);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($retryCount < $maxRetries) {
                    $retryCount++;
                    Log::warning('百度翻译连接失败，准备重试', [
                        'error' => $e->getMessage(),
                        'source' => $sourceLang,
                        'target' => $targetLang,
                        'retry_count' => $retryCount,
                    ]);
                    sleep(1);
                    continue;
                }

                throw new FailedException('百度翻译连接超时，请检查网络连接或稍后重试: '.$e->getMessage());
            } catch (\Exception $e) {
                throw new FailedException('百度翻译失败: '.$e->getMessage());
            }
        }

        throw new FailedException('百度翻译失败：已达到最大重试次数');
    }

    /**
     * 处理响应.
     */
    protected function handleResponse($response, int $retryCount, int $maxRetries, string $sourceLang, string $targetLang): string
    {
        if (!$response->successful()) {
            $errorBody = $response->body();
            Log::warning('百度翻译请求失败', [
                'status' => $response->status(),
                'body' => $errorBody,
                'source' => $sourceLang,
                'target' => $targetLang,
                'retry_count' => $retryCount,
            ]);

            // 如果是 4xx 错误（客户端错误），不重试
            if ($response->status() >= 400 && $response->status() < 500) {
                throw new FailedException('百度翻译请求失败: '.$errorBody);
            }

            // 如果是服务器错误或超时，重试
            if ($retryCount < $maxRetries) {
                sleep(1);
                throw new \Exception('需要重试');
            }

            throw new FailedException('百度翻译请求失败: '.$errorBody);
        }

        $data = $response->json();

        // 检查是否有错误码
        if (isset($data['error_code'])) {
            $errorMsg = $data['error_msg'] ?? '未知错误';
            $errorCode = $data['error_code'];

            // 错误码说明（参考百度翻译API文档）
            $errorMessages = [
                52000 => '成功',
                52001 => '请求超时，请重试',
                52002 => '系统错误，请稍后重试',
                52003 => '未授权用户，请检查appid是否正确',
                54000 => '必填参数为空',
                54001 => '签名错误或token错误',
                54003 => '访问频率受限',
                54004 => '账户余额不足',
                54005 => '长query请求频繁',
                58000 => '客户端IP非法',
                58001 => '译文语言方向不支持',
                58002 => '服务当前已关闭',
                58003 => '此IP已被封禁',
                58004 => '模型参数错误',
                59002 => '翻译指令过长',
                59003 => '请求文本过长',
                59004 => 'QPS超限',
                59005 => 'tag_handling 参数非法',
                59006 => '标签解析失败',
                59007 => 'ignore_tags长度超限',
                90107 => '认证未通过或未生效',
                20003 => '请求内容存在安全风险',
            ];

            $errorDescription = $errorMessages[$errorCode] ?? $errorMsg;

            Log::error('百度翻译API错误', [
                'error_code' => $errorCode,
                'error_msg' => $errorDescription,
                'source' => $sourceLang,
                'target' => $targetLang,
            ]);

            throw new FailedException("百度翻译错误 ({$errorCode}): {$errorDescription}");
        }

        // 获取翻译结果
        $translated = $data['trans_result'][0]['dst'] ?? '';

        if (empty($translated)) {
            Log::warning('百度翻译返回内容为空', [
                'response' => $data,
                'source' => $sourceLang,
                'target' => $targetLang,
            ]);
            throw new FailedException('百度翻译返回内容为空');
        }

        return $translated;
    }

    /**
     * 映射语言代码
     */
    protected function mapLanguageCode(string $lang): string
    {
        $baiduLangMap = [
            'zh' => 'zh',      // 中文
            'en' => 'en',      // 英语
            'th' => 'th',      // 泰语
            'vi' => 'vie',     // 越南语
        ];

        return $baiduLangMap[$lang] ?? $lang;
    }
}
