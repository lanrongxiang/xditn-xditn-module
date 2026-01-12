<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig;
use Modules\System\Support\Configure;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 微信配置
 *
 * @subgroupDescription  后台系统管理->微信配置
 */
class WechatConfigController extends Controller
{
    public function __construct(
        protected readonly SystemConfig $model
    ) {
    }

    /**
     * 保存微信配置.
     *
     * @bodyParam driver string required 微信类型（official_account/mini_program）
     * @bodyParam config object 微信配置
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        $driver = $request->get('driver');
        $config = Configure::parse("wechat.$driver", $request->except('driver'));

        return $this->model->storeBy($config);
    }

    /**
     * 获取微信配置.
     *
     * @urlParam driver string required 微信类型（official_account/mini_program）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 微信配置
     *
     * @param $driver
     *
     * @return mixed
     */
    public function show($driver = null)
    {
        return config("wechat.$driver");
    }
}
