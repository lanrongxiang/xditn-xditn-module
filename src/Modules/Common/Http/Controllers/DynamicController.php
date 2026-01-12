<?php

// 这是一个 demo 控制器
// 可以删除
// 只做演示用

namespace Modules\Common\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Common\Tables\Permission;
use Modules\Common\Tables\Role;
use XditnModule\Base\CatchController;

/**
 * @group 管理端
 *
 * @subgroup 公共演示
 *
 * @subgroupDescription  后台公共演示
 */
class DynamicController extends CatchController
{
    public function permission(Permission $permission, Request $request)
    {
        return $permission($request->get('key'));
    }

    public function role(Role $role, Request $request)
    {
        return $role($request->get('key'));
    }
}
