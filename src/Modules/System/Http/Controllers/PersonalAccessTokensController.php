<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Modules\System\Models\PersonalAccessTokens;
use Modules\User\Models\User;
use XditnModule\Base\CatchController as Controller;
use XditnModule\Facade\Admin;

/**
 * @group 管理端
 *
 * @subgroup 个人访问令牌
 *
 * @subgroupDescription  后台系统管理->个人访问令牌
 */
class PersonalAccessTokensController extends Controller
{
    public function __construct(
        protected readonly PersonalAccessTokens $model
    ) {
    }

    /**
     * 个人访问令牌列表.
     *
     * @urlParam page int 页码
     * @urlParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField limit int 每页数量
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField data object[] 数据
     * @responseField data[].id int 令牌ID
     * @responseField data[].name string 令牌名称
     * @responseField data[].tokenable_id int 用户ID
     * @responseField data[].username string 用户名
     * @responseField data[].login_ip string 登录IP
     * @responseField data[].location string 登录位置
     * @responseField data[].last_used_at string 最后使用时间
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->addSelect([
                'username' => User::whereColumn('id', $this->model->getTable().'.tokenable_id')
                    ->select(DB::raw('username')),
            ])
                ->join('log_login', 'log_login.token_id', $this->model->getTable().'.id')
                ->addSelect('log_login.login_ip', 'log_login.location');
        })->getList();
    }

    /**
     * 删除个人访问令牌.
     *
     * @urlParam id int required 令牌ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id)
    {
        if ($this->model->deletesBy($id)) {
            Admin::clearUserPersonalToken($id);

        }

        return true;
    }
}
