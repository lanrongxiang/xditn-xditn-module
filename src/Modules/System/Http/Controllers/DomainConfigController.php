<?php

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig as Config;
use Modules\System\Support\Configure;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 域名配置
 *
 * @subgroupDescription  后台系统管理->域名配置
 */
class DomainConfigController extends Controller
{
    /**
     * 域名配置列表.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 域名配置
     *
     * @return mixed
     */
    public function index()
    {
        return \config('domain');
    }

    /**
     * 保存域名配置.
     *
     * @bodyParam type string required 域名类型
     * @bodyParam config object 域名配置
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param Request $request
     * @param Config $configModel
     *
     * @return mixed
     */
    public function store(Request $request, Config $configModel)
    {
        $type = $request->get('type');

        return $configModel->storeBy(Configure::parse("domain.$type", $request->except('type')));
    }

}
