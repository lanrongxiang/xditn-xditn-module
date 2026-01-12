<?php

declare(strict_types=1);

namespace Modules\Common\Services\Translation;

use Illuminate\Container\Container;
use Modules\Common\Enums\TranslationMethod;
use Modules\Common\Exceptions\TranslationMethodNotSupportException;

/**
 * 翻译工厂类.
 */
class TranslationFactory
{
    /**
     * 创建翻译实例.
     *
     * @param TranslationMethod $method 翻译方法
     *
     * @return TranslationInterface
     */
    public static function make(TranslationMethod $method): TranslationInterface
    {
        // 使用 Laravel 容器解析依赖，支持依赖注入
        $container = Container::getInstance();

        return match ($method) {
            TranslationMethod::AI => $container->make(AiTranslation::class),
            TranslationMethod::BAIDU => $container->make(BaiduTranslation::class),
            TranslationMethod::GOOGLE => $container->make(GoogleTranslation::class),
            default => throw new TranslationMethodNotSupportException("翻译方法 {$method->value} 不支持"),
        };
    }
}
