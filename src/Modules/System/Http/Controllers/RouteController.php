<?php

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Modules\System\Support\Routes;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 路由管理
 *
 * @subgroupDescription  后台系统管理->路由管理
 */
class RouteController extends Controller
{
    /**
     * 路由列表.
     *
     * @urlParam page int 页码
     * @urlParam limit int 每页数量
     * @urlParam method string HTTP 方法
     * @urlParam uri string 路由 URI
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField limit int 每页数量
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField data object[] 数据
     * @responseField data[].method string HTTP 方法
     * @responseField data[].uri string 路由 URI
     * @responseField data[].name string 路由名称
     * @responseField data[].action string 控制器方法
     *
     * @param Routes $route
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Routes $route, Request $request)
    {
        return $route->all($request->all());
    }

    /**
     * 缓存路由.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data int 退出码
     *
     * @return int
     */
    public function cache()
    {
        return Artisan::call('route:cache');
    }
}
