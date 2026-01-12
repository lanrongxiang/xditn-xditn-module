<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Modules\System\Models\SystemSmsCode;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 短信验证码
 *
 * @subgroupDescription  后台系统管理->短信验证码
 */
class SystemSmsCodeController extends Controller
{
    public function __construct(
        protected readonly SystemSmsCode $model
    ) {
    }

    /**
     * 短信验证码列表.
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
     * @responseField data[].id int ID
     * @responseField data[].mobile string 手机号
     * @responseField data[].code string 验证码
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 删除短信验证码
     *
     * @urlParam id int required 验证码ID
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
        return $this->model->deleteBy($id);
    }
}
