<?php

declare(strict_types=1);

namespace Modules\Common\Services\Translation;

/**
 * 翻译接口.
 */
interface TranslationInterface
{
    /**
     * 翻译文本.
     *
     * @param string $text 要翻译的文本
     * @param string $sourceLang 源语言代码（如：zh）
     * @param string $targetLang 目标语言代码（如：en）
     *
     * @return string 翻译后的文本
     */
    public function translate(string $text, string $sourceLang, string $targetLang): string;
}
