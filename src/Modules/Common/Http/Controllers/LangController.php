<?php

namespace Modules\Common\Http\Controllers;

use Illuminate\Support\Facades\File;

/**
 * @group 管理端
 *
 * @subgroup 多语言
 *
 * @subgroupDescription  后台多语言管理
 */
class LangController
{
    /**
     * 获取语言包.
     *
     * @param string $lang 语言代码 (zh, en, th, vi)
     */
    public function translate(string $lang): array
    {
        // 支持的语言列表
        $supportedLanguages = ['zh', 'en', 'th', 'vi'];

        // 如果语言不支持，使用默认语言
        if (!in_array($lang, $supportedLanguages)) {
            $lang = 'zh';
        }

        $translations = [];
        $langPath = lang_path($lang);

        // 检查语言目录是否存在
        if (File::exists($langPath)) {
            $files = File::allFiles($langPath);

            foreach ($files as $file) {
                $translations[$file->getFilenameWithoutExtension()] = require $file->getRealPath();
            }
        }

        return $translations;
    }
}
