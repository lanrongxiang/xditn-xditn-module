<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig;
use Modules\System\Support\Configure;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 翻译配置管理
 *
 * @subgroupDescription  后台公共管理->翻译配置管理
 */
class TranslationConfigController extends Controller
{
    /**
     * 获取翻译配置.
     *
     * 获取指定翻译服务的配置信息
     *
     * @urlParam driver string 翻译服务:ai baidu google
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 翻译配置信息
     */
    public function show($driver)
    {
        return config("translation.{$driver}");
    }

    /**
     * 保存翻译配置.
     *
     * 保存指定翻译服务的配置信息
     *
     * @bodyParam driver string 翻译服务:ai baidu google
     * @bodyParam config object 配置信息
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     */
    public function store(Request $request, SystemConfig $config)
    {
        return $config->storeBy(Configure::parse('translation', $request->all()));
    }
}
