<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemSmsTemplate;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 短信模板
 *
 * @subgroupDescription  后台系统管理->短信模板
 */
class SystemSmsTemplateController extends Controller
{
    public function __construct(
        protected readonly SystemSmsTemplate $model
    ) {
    }

    /**
     * 短信模板列表.
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
     * @responseField data[].identify string 标识
     * @responseField data[].template_id string 模板ID
     * @responseField data[].content string 模板内容
     * @responseField data[].variables array 变量
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 新增短信模板
     *
     * @bodyParam identify string required 标识
     * @bodyParam template_id string required 模板ID
     * @bodyParam content string required 模板内容
     * @bodyParam variables array 变量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 模板信息
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * 短信模板详情.
     *
     * @urlParam id int required 模板ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 模板详情
     * @responseField data.id int ID
     * @responseField data.identify string 标识
     * @responseField data.template_id string 模板ID
     * @responseField data.content string 模板内容
     * @responseField data.variables array 变量
     *
     * @param $id
     *
     * @return mixed
     */
    public function show($id)
    {
        return $this->model->select(['identify', 'template_id', 'content', 'variables'])->find($id);
    }

    /**
     * 更新短信模板
     *
     * @urlParam id int required 模板ID
     *
     * @bodyParam identify string 标识
     * @bodyParam template_id string 模板ID
     * @bodyParam content string 模板内容
     * @bodyParam variables array 变量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 模板信息
     *
     * @param $id
     * @param Request $request
     *
     * @return mixed
     */
    public function update($id, Request $request)
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除短信模板
     *
     * @urlParam id int required 模板ID
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
