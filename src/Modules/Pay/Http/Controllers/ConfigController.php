<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig;
use Modules\System\Support\Configure;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 支付配置管理
 *
 * @subgroupDescription  后台支付管理->支付配置管理
 */
class ConfigController extends Controller
{
    /**
     * 获取支付配置.
     *
     * 获取指定支付网关的配置信息
     *
     * @urlParam driver string 支付网关:alipay wechat paypal
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 支付配置信息
     */
    public function show($driver)
    {
        return config("pay.{$driver}");
    }

    /**
     * 保存支付配置.
     *
     * 保存指定支付网关的配置信息
     *
     * @bodyParam driver string 支付网关:alipay wechat paypal
     * @bodyParam config object 配置信息
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     */
    public function store(Request $request, SystemConfig $config)
    {
        return $config->storeBy(Configure::parse('pay', $request->all()));
    }
}
