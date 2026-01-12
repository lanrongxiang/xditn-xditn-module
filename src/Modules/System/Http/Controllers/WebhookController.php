<?php

declare(strict_types=1);

namespace Modules\System\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\Webhooks;
use Modules\System\Support\Webhook;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup Webhook
 *
 * @subgroupDescription  后台系统管理->Webhook
 */
class WebhookController extends Controller
{
    public function __construct(
        protected readonly Webhooks $model
    ) {
    }

    /**
     * Webhook 列表.
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
     * @responseField data[].name string 名称
     * @responseField data[].url string Webhook URL
     * @responseField data[].status int 状态
     * @responseField data[].created_at string 创建时间
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 新增 Webhook.
     *
     * @bodyParam name string required 名称
     * @bodyParam url string required Webhook URL
     * @bodyParam secret string 密钥
     * @bodyParam status int 状态
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * Webhook 详情.
     *
     * @urlParam id int required Webhook ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object Webhook 详情
     * @responseField data.id int ID
     * @responseField data.name string 名称
     * @responseField data.url string Webhook URL
     * @responseField data.secret string 密钥
     * @responseField data.status int 状态
     * @responseField data.created_at string 创建时间
     *
     * @return mixed
     */
    public function show($id)
    {
        return $this->model->firstBy($id);
    }

    /**
     * 更新 Webhook.
     *
     * @urlParam id int required Webhook ID
     *
     * @bodyParam name string 名称
     * @bodyParam url string Webhook URL
     * @bodyParam secret string 密钥
     * @bodyParam status int 状态
     *
     * @return mixed
     */
    public function update($id, Request $request)
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除 Webhook.
     *
     * @urlParam id int required Webhook ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->model->deleteBy($id);
    }

    /**
     * 启用/禁用 Webhook.
     *
     * @urlParam id int required Webhook ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @return mixed
     */
    public function enable($id)
    {
        return $this->model->toggleBy($id);
    }

    /**
     * 测试 Webhook.
     *
     * @urlParam id int required Webhook ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data bool 是否成功
     *
     * @return bool
     */
    public function test($id)
    {
        $webhook = new Webhook($this->model->firstBy($id));

        return $webhook->send('Hello World, I am a robot!');
    }
}
