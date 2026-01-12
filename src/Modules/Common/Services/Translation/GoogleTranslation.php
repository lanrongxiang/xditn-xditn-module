<?php

declare(strict_types=1);

namespace Modules\Common\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use XditnModule\Exceptions\FailedException;

/**
 * Google 翻译实现.
 */
class GoogleTranslation implements TranslationInterface
{
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

        $apiKey = config('services.google.translate_api_key');
        if (!$apiKey) {
            throw new FailedException('未配置 Google Translate API Key');
        }

        $maxRetries = 2;
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                $response = Http::withOptions([
                    'timeout' => 30,
                    'connect_timeout' => 10,
                ])->post('https://translation.googleapis.com/language/translate/v2', [
                    'key' => $apiKey,
                    'q' => $text,
                    'source' => $sourceLang,
                    'target' => $targetLang,
                    'format' => 'text',
                ]);

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    Log::warning('Google 翻译请求失败', [
                        'status' => $response->status(),
                        'body' => $errorBody,
                        'source' => $sourceLang,
                        'target' => $targetLang,
                        'retry_count' => $retryCount,
                    ]);

                    // 如果是 4xx 错误（客户端错误），不重试
                    if ($response->status() >= 400 && $response->status() < 500) {
                        throw new FailedException('Google 翻译请求失败: '.$errorBody);
                    }

                    // 如果是服务器错误或超时，重试
                    if ($retryCount < $maxRetries) {
                        $retryCount++;
                        sleep(1);
                        continue;
                    }

                    throw new FailedException('Google 翻译请求失败: '.$errorBody);
                }

                $data = $response->json();
                $translated = $data['data']['translations'][0]['translatedText'] ?? '';

                if (empty($translated)) {
                    Log::warning('Google 翻译返回内容为空', [
                        'response' => $data,
                        'source' => $sourceLang,
                        'target' => $targetLang,
                    ]);
                    throw new FailedException('Google 翻译返回内容为空');
                }

                return $translated;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // 连接超时或网络错误，重试
                if ($retryCount < $maxRetries) {
                    $retryCount++;
                    Log::warning('Google 翻译连接失败，准备重试', [
                        'error' => $e->getMessage(),
                        'source' => $sourceLang,
                        'target' => $targetLang,
                        'retry_count' => $retryCount,
                    ]);
                    sleep(1);
                    continue;
                }

                Log::error('Google 翻译调用失败（连接超时）', [
                    'error' => $e->getMessage(),
                    'source' => $sourceLang,
                    'target' => $targetLang,
                    'retry_count' => $retryCount,
                ]);

                throw new FailedException('Google 翻译连接超时，请检查网络连接或稍后重试: '.$e->getMessage());
            } catch (\Exception $e) {
                Log::error('Google 翻译失败', [
                    'error' => $e->getMessage(),
                    'source' => $sourceLang,
                    'target' => $targetLang,
                    'retry_count' => $retryCount,
                ]);

                throw new FailedException('Google 翻译失败: '.$e->getMessage());
            }
        }

        throw new FailedException('Google 翻译失败：已达到最大重试次数');
    }
}
