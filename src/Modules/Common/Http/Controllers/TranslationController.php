<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Common\Services\TranslationService;
use XditnModule\Base\XditnModuleController as Controller;
use XditnModule\Exceptions\FailedException;

/**
 * @group 管理端
 *
 * @subgroup 翻译服务
 *
 * @subgroupDescription 多语言字段翻译服务
 */
class TranslationController extends Controller
{
    public function __construct(
        protected readonly TranslationService $translationService
    ) {
    }

    /**
     * 翻译多语言字段.
     *
     * @bodyParam fields object required 要翻译的字段，如：{"title": {"zh": "标题"}, "description": {"zh": "描述"}}
     * @bodyParam source_lang string 源语言代码，如：zh（可选，默认使用配置中的 default_source_lang）
     * @bodyParam target_langs array 目标语言代码数组，如：["en", "th", "vi"]（可选，默认使用配置中的 target_languages）
     * @bodyParam method string 翻译方法：ai、google、baidu（可选，默认使用配置中的 default_translate_service）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 翻译后的字段数据
     */
    public function translate(Request $request): array
    {
        $fields = $request->input('fields', []);
        $sourceLang = $request->input('source_lang');
        $targetLangs = $request->input('target_langs');
        $method = $request->input('method');

        if (empty($fields)) {
            throw new FailedException('请提供要翻译的字段');
        }

        if ($targetLangs !== null && !is_array($targetLangs)) {
            throw new FailedException('目标语言必须是数组');
        }

        try {
            return $this->translationService->translateMultilingualFields(
                $fields,
                $sourceLang,
                $targetLangs,
                $method
            );
        } catch (\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }

    /**
     * 一键翻译（使用配置中的默认参数）.
     *
     * @bodyParam fields object required 要翻译的字段，如：{"title": {"zh": "标题"}, "description": {"zh": "描述"}}
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 翻译后的字段数据
     */
    public function autoTranslate(Request $request): array
    {
        $fields = $request->input('fields', []);

        if (empty($fields)) {
            throw new FailedException('请提供要翻译的字段');
        }

        try {
            return $this->translationService->autoTranslate($fields);
        } catch (\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }
}
