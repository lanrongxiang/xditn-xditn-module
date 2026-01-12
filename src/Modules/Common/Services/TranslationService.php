<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Support\Facades\Log;
use Modules\Common\Enums\TranslationMethod;
use Modules\Common\Services\Translation\TranslationFactory;
use XditnModule\Exceptions\FailedException;
use XditnModule\Traits\DB\Cacheable;

/**
 * 翻译服务.
 */
class TranslationService
{
    use Cacheable;

    /**
     * 缓存前缀.
     */
    protected ?string $cachePrefix = 'translation';

    /**
     * 缓存标签.
     */
    protected array $cacheTags = ['translation'];

    /**
     * 获取基础配置.
     */
    protected function getBaseConfig(): array
    {
        return config('translation.base', [
            'default_translate_service' => 'baidu',
            'enable_log' => 1,
            'cache_time' => 60,
            'default_source_lang' => 'zh',
            'target_languages' => ['en', 'th', 'vi'],
        ]);
    }

    /**
     * 是否启用日志.
     */
    protected function isLogEnabled(): bool
    {
        $config = $this->getBaseConfig();

        return (bool) ($config['enable_log'] ?? 1);
    }

    /**
     * 获取缓存时间（秒）.
     */
    protected function getTranslationCacheTime(): int
    {
        $config = $this->getBaseConfig();

        return (int) ($config['cache_time'] ?? 60);
    }

    /**
     * 获取默认翻译服务
     */
    protected function getDefaultTranslateService(): string
    {
        $config = $this->getBaseConfig();

        return $config['default_translate_service'] ?? 'baidu';
    }

    /**
     * 翻译文本.
     *
     * @param string $text 要翻译的文本
     * @param string $sourceLang 源语言代码（如：zh）
     * @param string $targetLang 目标语言代码（如：en）
     * @param string|null $method 翻译方法：ai, google, baidu，如果为 null 则使用配置中的默认值
     *
     * @return string 翻译后的文本
     *
     * @throws FailedException
     */
    public function translate(string $text, string $sourceLang, string $targetLang, ?string $method = null): string
    {
        if (empty($text)) {
            return '';
        }

        if ($sourceLang === $targetLang) {
            return $text;
        }

        // 使用配置中的默认翻译服务
        if ($method === null) {
            $method = $this->getDefaultTranslateService();
        }

        // 生成缓存键
        $cacheKey = $this->buildTranslationCacheKey($text, $sourceLang, $targetLang, $method);

        // 获取缓存时间
        $cacheTime = $this->getTranslationCacheTime();

        // 如果缓存时间为 0，不使用缓存
        if ($cacheTime <= 0) {
            return $this->doTranslate($text, $sourceLang, $targetLang, $method);
        }

        // 使用 Cacheable Trait 的 remember 方法
        return $this->remember(
            $cacheKey,
            fn () => $this->doTranslate($text, $sourceLang, $targetLang, $method),
            $cacheTime
        ) ?? '';
    }

    /**
     * 执行实际翻译.
     */
    protected function doTranslate(string $text, string $sourceLang, string $targetLang, string $method): string
    {
        try {
            // 将字符串方法转换为枚举
            $translationMethod = $this->parseMethod($method);

            // 使用工厂创建翻译实例
            $translator = TranslationFactory::make($translationMethod);

            $translated = $translator->translate($text, $sourceLang, $targetLang);

            if ($this->isLogEnabled()) {
                Log::info('翻译成功', [
                    'method' => $method,
                    'source' => $sourceLang,
                    'target' => $targetLang,
                    'text_length' => strlen($text),
                    'translated_length' => strlen($translated),
                ]);
            }

            return $translated;
        } catch (\Exception $e) {
            if ($this->isLogEnabled()) {
                Log::error('翻译失败', [
                    'method' => $method,
                    'source' => $sourceLang,
                    'target' => $targetLang,
                    'error' => $e->getMessage(),
                ]);
            }

            throw new FailedException('翻译失败: '.$e->getMessage());
        }
    }

    /**
     * 生成翻译缓存键.
     */
    protected function buildTranslationCacheKey(string $text, string $sourceLang, string $targetLang, string $method): string
    {
        return md5($method.':'.$sourceLang.':'.$targetLang.':'.$text);
    }

    /**
     * 清除翻译缓存.
     */
    public function clearTranslationCache(): bool
    {
        return $this->flushCache();
    }

    /**
     * 清除指定文本的翻译缓存.
     */
    public function clearTextCache(string $text, string $sourceLang, string $targetLang, string $method): bool
    {
        $cacheKey = $this->buildTranslationCacheKey($text, $sourceLang, $targetLang, $method);

        return $this->forgetCache($cacheKey);
    }

    /**
     * 批量翻译多语言字段.
     *
     * @param array $data 包含多语言字段的数据，如：['title' => ['zh' => '标题'], 'description' => ['zh' => '描述']]
     * @param string|null $sourceLang 源语言代码（如：zh），如果为 null 则使用配置中的默认值
     * @param array|null $targetLangs 目标语言代码数组（如：['en', 'th', 'vi']），如果为 null 则使用配置中的默认值
     * @param string|null $method 翻译方法，如果为 null 则使用配置中的默认值
     *
     * @return array 翻译后的数据
     */
    public function translateMultilingualFields(array $data, ?string $sourceLang = null, ?array $targetLangs = null, ?string $method = null): array
    {
        $baseConfig = $this->getBaseConfig();

        // 使用配置中的默认值
        if ($sourceLang === null) {
            $sourceLang = $baseConfig['default_source_lang'] ?? 'zh';
        }

        if ($targetLangs === null) {
            $targetLangs = $baseConfig['target_languages'] ?? ['en', 'th', 'vi'];
        }

        if ($method === null) {
            $method = $this->getDefaultTranslateService();
        }

        $result = [];

        foreach ($data as $field => $value) {
            if (!is_array($value) || !isset($value[$sourceLang])) {
                $result[$field] = $value;

                continue;
            }

            $sourceText = $value[$sourceLang];
            if (empty($sourceText)) {
                $result[$field] = $value;

                continue;
            }

            $translated = $value;
            foreach ($targetLangs as $targetLang) {
                if ($targetLang === $sourceLang) {
                    continue;
                }

                try {
                    $translated[$targetLang] = $this->translate($sourceText, $sourceLang, $targetLang, $method);
                } catch (\Exception $e) {
                    if ($this->isLogEnabled()) {
                        Log::error('翻译失败', [
                            'field' => $field,
                            'source' => $sourceLang,
                            'target' => $targetLang,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    // 翻译失败时保留原值或设为空
                    $translated[$targetLang] = $value[$targetLang] ?? '';
                }
            }

            $result[$field] = $translated;
        }

        return $result;
    }

    /**
     * 一键翻译（使用配置中的默认参数）.
     *
     * @param array $data 包含多语言字段的数据，如：['title' => ['zh' => '标题'], 'description' => ['zh' => '描述']]
     *
     * @return array 翻译后的数据
     */
    public function autoTranslate(array $data): array
    {
        return $this->translateMultilingualFields($data);
    }

    /**
     * 解析翻译方法字符串为枚举.
     *
     * @param string $method 翻译方法字符串
     *
     * @throws FailedException
     */
    protected function parseMethod(string $method): TranslationMethod
    {
        return match (strtolower($method)) {
            'ai' => TranslationMethod::AI,
            'baidu' => TranslationMethod::BAIDU,
            'google' => TranslationMethod::GOOGLE,
            default => throw new FailedException("不支持的翻译方法: {$method}"),
        };
    }
}
