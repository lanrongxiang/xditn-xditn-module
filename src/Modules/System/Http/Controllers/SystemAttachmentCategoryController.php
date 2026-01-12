<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemAttachmentCategory;
use Modules\System\Models\SystemAttachments;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 附件分类
 *
 * @subgroupDescription  后台系统管理->附件分类
 */
class SystemAttachmentCategoryController extends Controller
{
    public function __construct(
        protected readonly SystemAttachmentCategory $model
    ) {
    }

    /**
     * 附件分类列表.
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
     * @responseField data[].name string 分类名称
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 新增附件分类.
     *
     * @bodyParam name string required 分类名称
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 分类信息
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
     * 更新附件分类.
     *
     * @urlParam id int required 分类ID
     *
     * @bodyParam name string 分类名称
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 分类信息
     *
     * @param $id
     * @param Request $request
     *
     * @return mixed
     */
    public function update($id, Request $request): mixed
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除附件分类.
     *
     * @urlParam id int required 分类ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data array 空数组
     *
     * @param $id
     * @param SystemAttachments $systemAttachments
     *
     * @return mixed
     */
    public function destroy($id, SystemAttachments $systemAttachments)
    {
        if ($this->model->deleteBy($id)) {
            // 更新附件所属分类为 0
            $systemAttachments->where('category_id', $id)->update([
                'category_id' => 0,
            ]);
        }

        return [];
    }
}
