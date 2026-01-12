<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Modules\System\Models\SystemCronTasksLog;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 定时任务日志
 *
 * @subgroupDescription  后台系统管理->定时任务日志
 */
class CronTasksLogController extends Controller
{
    public function __construct(
        protected readonly SystemCronTasksLog $model
    ) {
    }

    /**
     * 日志列表.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField limit int 每页数量
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField data object[] 数据
     * @responseField data[].id int ID
     * @responseField data[].task_id string 名称
     * @responseField data[].start_at string 任务参数
     * @responseField data[].end_at string 任务开始时间
     * @responseField data[].consuming string 任务耗时
     * @responseField data[].status int 任务状态
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 删除日志.
     *
     * @urlParam id int required 日志ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @param $id
     *
     * @return bool
     */
    public function destroy($id)
    {
        return $this->model->deletesBy($id);
    }
}
