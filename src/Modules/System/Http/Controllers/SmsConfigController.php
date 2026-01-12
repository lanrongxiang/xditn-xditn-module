<?php

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig as Config;
use Modules\System\Support\Configure;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 短信配置
 *
 * @subgroupDescription  后台系统管理->短信配置
 */
class SmsConfigController extends Controller
{
    /**
     * 保存短信配置.
     *
     * @bodyParam default string 默认短信通道
     * @bodyParam channel string 短信通道
     * @bodyParam config object 通道配置
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
        if ($default = $request->get('default')) {
            return $configModel->storeBy([
                'sms.default' => $default,
            ]);
        }

        $driver = $request->get('channel');

        return $configModel->storeBy(Configure::parse("sms.$driver", $request->except('channel')));
    }

    /**
     * 获取短信配置.
     *
     * @urlParam driver string required 短信通道
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 短信配置
     *
     * @param $driver
     *
     * @return mixed
     */
    public function show($driver)
    {
        return \config('sms.'.$driver);
    }
}
