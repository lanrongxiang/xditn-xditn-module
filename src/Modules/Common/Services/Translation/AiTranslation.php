<?php

declare(strict_types=1);

namespace Modules\Common\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Ai\Models\AiProviders;
use XditnModule\Exceptions\FailedException;

/**
 * AI 翻译实现.
 */
class AiTranslation implements TranslationInterface
{
    /**
     * 支持的语言映射.
     */
    protected const LANGUAGE_MAP = [
        'zh' => '中文',
        'en' => 'English',
        'th' => 'ไทย',
        'vi' => 'Tiếng Việt',
    ];

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

        // 直接查询 AI 服务商（优先使用 OpenAI）
        $provider = AiProviders::query()
            ->where('provider', 'openai')
            ->where('status', 1)
            ->first();

        if (!$provider) {
            // 如果没有 OpenAI，尝试使用其他启用的服务商
            $provider = AiProviders::query()
                ->where('status', 1)
                ->first();
        }

        if (!$provider) {
            throw new FailedException('未找到可用的 AI 服务商，请先在 AI 模块中配置 AI 服务商');
        }

        // 根据服务商类型选择默认模型
        $modelName = $this->getDefaultModelName($provider->provider);

        $sourceLanguage = self::LANGUAGE_MAP[$sourceLang] ?? $sourceLang;
        $targetLanguage = self::LANGUAGE_MAP[$targetLang] ?? $targetLang;

        $prompt = "请将以下{$sourceLanguage}文本翻译成{$targetLanguage}，只返回翻译结果，不要添加任何解释或说明：\n\n{$text}";

        return $this->callAiApi($provider, $modelName, $prompt);
    }

    /**
     * 根据服务商类型获取默认模型名称.
     */
    protected function getDefaultModelName(string $providerType): string
    {
        return match ($providerType) {
            'openai' => 'gpt-3.5-turbo',
            'anthropic' => 'claude-3-sonnet-20240229',
            'google' => 'gemini-pro',
            'dashscope' => 'qwen-turbo',
            'tencent' => 'hunyuan-lite',
            'baidu' => 'ernie-bot-turbo',
            'moonshot' => 'moonshot-v1-8k',
            'deepseek' => 'deepseek-chat',
            'zhipu' => 'glm-4',
            default => 'gpt-3.5-turbo',
        };
    }

    /**
     * 获取正确的 API URL.
     */
    protected function getApiUrl(?string $customUrl, string $providerType): string
    {
        // 如果自定义 URL 为空或包含错误的路径，使用默认 URL
        if (empty($customUrl) || str_contains($customUrl, '/v1/responses')) {
            return match ($providerType) {
                'openai' => 'https://api.openai.com/v1/chat/completions',
                'anthropic' => 'https://api.anthropic.com/v1/messages',
                'google' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
                'dashscope' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
                'tencent' => 'https://hunyuan.tencentcloudapi.com/',
                'baidu' => 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions',
                'moonshot' => 'https://api.moonshot.cn/v1/chat/completions',
                'deepseek' => 'https://api.deepseek.com/v1/chat/completions',
                'zhipu' => 'https://open.bigmodel.cn/api/paas/v4/chat/completions',
                default => 'https://api.openai.com/v1/chat/completions',
            };
        }

        return $customUrl;
    }

    /**
     * 调用 AI API.
     */
    protected function callAiApi(object $provider, string $modelName, string $prompt): string
    {
        $apiKey = $provider->api_key;
        $providerType = $provider->provider ?? 'openai';

        // 获取正确的 API URL（如果数据库中的 URL 为空或错误，使用默认值）
        $apiUrl = $this->getApiUrl($provider->api_url, $providerType);

        if (empty($apiKey)) {
            throw new FailedException('AI 服务商的 API Key 未配置');
        }

        $maxRetries = 2;
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                $response = Http::withOptions([
                    'timeout' => 120, // 总超时时间 120 秒
                    'connect_timeout' => 30, // 连接超时 30 秒
                ])->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])->post($apiUrl, [
                    'model' => $modelName,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                ]);

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    Log::warning('AI 翻译请求失败', [
                        'status' => $response->status(),
                        'body' => $errorBody,
                        'provider' => $providerType,
                        'model' => $modelName,
                        'retry_count' => $retryCount,
                    ]);

                    // 如果是 4xx 错误（客户端错误），不重试
                    if ($response->status() >= 400 && $response->status() < 500) {
                        throw new FailedException('AI 翻译请求失败: '.$errorBody);
                    }

                    // 如果是服务器错误或超时，重试
                    if ($retryCount < $maxRetries) {
                        $retryCount++;
                        sleep(2); // 等待 2 秒后重试
                        continue;
                    }

                    throw new FailedException('AI 翻译请求失败: '.$errorBody);
                }

                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';

                if (empty($content)) {
                    Log::warning('AI 翻译返回内容为空', [
                        'response' => $data,
                        'provider' => $providerType,
                        'model' => $modelName,
                    ]);
                    throw new FailedException('AI 翻译返回内容为空');
                }

                return trim($content);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // 连接超时或网络错误，重试
                if ($retryCount < $maxRetries) {
                    $retryCount++;
                    Log::warning('AI 翻译连接失败，准备重试', [
                        'error' => $e->getMessage(),
                        'provider' => $providerType,
                        'model' => $modelName,
                        'retry_count' => $retryCount,
                    ]);
                    sleep(2);
                    continue;
                }

                Log::error('AI 翻译调用失败（连接超时）', [
                    'error' => $e->getMessage(),
                    'provider' => $providerType,
                    'model' => $modelName,
                    'retry_count' => $retryCount,
                ]);

                throw new FailedException('AI 翻译连接超时，请检查网络连接或稍后重试: '.$e->getMessage());
            } catch (\Exception $e) {
                Log::error('AI 翻译调用失败', [
                    'error' => $e->getMessage(),
                    'provider' => $providerType,
                    'model' => $modelName,
                    'retry_count' => $retryCount,
                ]);

                throw new FailedException('AI 翻译失败: '.$e->getMessage());
            }
        }

        throw new FailedException('AI 翻译失败：已达到最大重试次数');
    }
}
