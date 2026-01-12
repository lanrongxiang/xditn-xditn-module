<?php

namespace Modules\System\Http\Controllers;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig as Config;
use Modules\System\Support\Configure;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 系统设置
 *
 * @subgroupDescription  系统设置
 */
class SettingController extends Controller
{
    /**
     * 保存系统设置.
     *
     * @bodyParam setting object 系统设置配置
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
        return $configModel->storeBy(Configure::parse('setting', $request->all()));
    }

    /**
     * 获取系统设置.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 系统设置配置
     *
     * @return Repository|Application|mixed|object|null
     */
    public function show()
    {
        return \config('setting', []);
    }
}
