<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemAttachments;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 附件管理
 *
 * @subgroupDescription  后台系统管理->附件管理
 */
class SystemAttachmentsController extends Controller
{
    public function __construct(
        protected readonly SystemAttachments $model
    ) {
    }

    /**
     * 附件列表.
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
     * @responseField data[].name string 文件名
     * @responseField data[].path string 文件路径
     * @responseField data[].size int 文件大小
     * @responseField data[].mime_type string MIME 类型
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 上传附件.
     *
     * @bodyParam file file required 文件
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 附件信息
     * @responseField data.id int ID
     * @responseField data.name string 文件名
     * @responseField data.path string 文件路径
     * @responseField data.size int 文件大小
     * @responseField data.mime_type string MIME 类型
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request): mixed
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * 删除附件.
     *
     * @urlParam id int required 附件ID
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
        return $this->model->deletesBy($id);
    }
}
