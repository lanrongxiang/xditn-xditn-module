<?php

declare(strict_types=1);

namespace Modules\Mail\Http\Controllers;

use Modules\Mail\Http\Requests\TemplateRequest;
use Modules\Mail\Models\MailTemplate;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 邮件模板管理
 *
 * @subgroupDescription 邮件模板管理接口
 */
class TemplateController extends Controller
{
    /**
     * @param MailTemplate $model
     */
    public function __construct(
        protected readonly MailTemplate $model,
    ) {
    }

    /**
     * 邮件模板列表.
     *
     * 获取邮件模板列表，支持分页和搜索
     *
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     * @queryParam name string 模板名称（模糊搜索）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 邮件模板列表
     * @responseField data[].id int 模板ID
     * @responseField data[].name string 模板名称
     * @responseField data[].code string 模板代码
     * @responseField data[].status int 状态:1=启用,2=禁用
     * @responseField data[].status_text string 状态文本
     * @responseField data[].created_at string 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 创建邮件模板
     *
     * 创建新的邮件模板
     *
     * @bodyParam name string required 模板名称
     * @bodyParam code string 模板代码
     * @bodyParam mode string 模板模式:html=HTML模式,blade=Blade模板模式
     * @bodyParam content string required 模板内容
     * @bodyParam status int 状态:1=启用,2=禁用
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 邮件模板信息
     * @responseField data.id int 模板ID
     * @responseField data.name string 模板名称
     * @responseField data.code string 模板代码
     * @responseField data.mode string 模板模式
     * @responseField data.content string 模板内容
     * @responseField data.status int 状态
     * @responseField data.status_text string 状态文本
     * @responseField data.created_at string 创建时间
     *
     * @param TemplateRequest $request
     *
     * @return mixed
     */
    public function store(TemplateRequest $request): mixed
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * 邮件模板详情.
     *
     * 获取指定邮件模板的详细信息
     *
     * @urlParam id int required 模板ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 邮件模板信息
     * @responseField data.id int 模板ID
     * @responseField data.name string 模板名称
     * @responseField data.code string 模板代码
     * @responseField data.mode string 模板模式
     * @responseField data.content string 模板内容
     * @responseField data.status int 状态:1=启用,2=禁用
     * @responseField data.status_text string 状态文本
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function show(mixed $id): mixed
    {
        return $this->model->firstBy($id);
    }

    /**
     * 更新邮件模板
     *
     * 更新指定邮件模板的信息
     *
     * @urlParam id int required 模板ID
     *
     * @bodyParam name string required 模板名称
     * @bodyParam code string 模板代码
     * @bodyParam mode string 模板模式:html=HTML模式,blade=Blade模板模式
     * @bodyParam content string required 模板内容
     * @bodyParam status int 状态:1=启用,2=禁用
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 邮件模板信息
     * @responseField data.id int 模板ID
     * @responseField data.name string 模板名称
     * @responseField data.code string 模板代码
     * @responseField data.mode string 模板模式
     * @responseField data.content string 模板内容
     * @responseField data.status int 状态
     * @responseField data.status_text string 状态文本
     * @responseField data.updated_at string 更新时间
     *
     * @param mixed $id
     * @param TemplateRequest $request
     *
     * @return mixed
     */
    public function update(mixed $id, TemplateRequest $request): mixed
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除邮件模板
     *
     * 删除指定的邮件模板
     *
     * @urlParam id int required 模板ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否删除成功
     *
     * @param mixed $id
     *
     * @return bool|null
     */
    public function destroy(mixed $id): ?bool
    {
        return $this->model->deleteBy($id);
    }

    /**
     * 切换模板状态
     *
     * 切换邮件模板的启用/禁用状态
     *
     * @urlParam id int required 模板ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否切换成功
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function enable(mixed $id): bool
    {
        return $this->model->toggleBy($id);
    }
}
