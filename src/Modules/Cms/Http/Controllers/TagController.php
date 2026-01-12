<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cms\Models\PostHasTags;
use Modules\Cms\Models\Tag;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 标签管理
 *
 * @subgroupDescription 后台内容管理->标签管理
 */
class TagController extends Controller
{
    public function __construct(
        protected readonly Tag $model
    ) {
    }

    /**
     * 标签列表.
     *
     * @queryParam name string 标签名称
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 标签数据
     * @responseField data[].id int 标签ID
     * @responseField data[].name string 标签名称
     * @responseField data[].created_at string 创建时间
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 新增标签.
     *
     * @bodyParam name string required 标签名称
     *
     * @responseField data int 标签ID
     */
    public function store(Request $request): mixed
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * 标签详情.
     *
     * @urlParam id int required 标签ID
     */
    public function show(mixed $id): mixed
    {
        return $this->model->firstBy($id);
    }

    /**
     * 更新标签.
     *
     * @urlParam id int required 标签ID
     *
     * @bodyParam name string required 标签名称
     */
    public function update(mixed $id, Request $request): mixed
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除标签.
     *
     * @urlParam id int required 标签ID
     */
    public function destroy(mixed $id): bool
    {
        return $this->model->deletesBy($id, callback: function ($ids) {
            foreach ($ids as $id) {
                PostHasTags::where('tag_id', $id)->delete();
            }
        });
    }
}
