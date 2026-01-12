<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Cms\Enums\ResourceType;
use Modules\Cms\Models\Resource;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 资源管理
 *
 * @subgroupDescription 后台内容管理->资源管理
 */
class ResourceController extends Controller
{
    public function __construct(
        protected readonly Resource $model
    ) {
    }

    /**
     * 资源列表.
     *
     * @queryParam type string 资源类型
     * @queryParam name string 资源名称
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField data object[] 资源数据
     * @responseField data[].id int 资源ID
     * @responseField data[].name string 资源名称
     * @responseField data[].type string 资源类型:1 banner 2 友情链接 3 广告
     * @responseField data[].url string 资源链接
     * @responseField data[].is_target int 是否打开新窗口:1 打开 2 不打开
     * @responseField data[].description string 资源描述
     * @responseField data[].is_visible int 是否可见:1 可见 2 不可见
     * @responseField data[].created_at string 创建时间
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 新增资源.
     *
     * @bodyParam name string required 资源名称
     * @bodyParam type string required 资源类型:1 banner 2 友情链接 3 广告
     * @bodyParam url string required 资源链接
     * @bodyParam is_target int required 是否打开新窗口:1 打开 2 不打开
     * @bodyParam description string 资源描述
     * @bodyParam is_visible int 是否可见:1 可见 2 不可见
     */
    public function store(Request $request): mixed
    {
        $data = $request->all();
        if (ResourceType::FRIEND_LINK->assert($data['type'])) {
            $data['content'] = '';
        }

        return $this->model->storeBy($data);
    }

    /**
     * 资源详情.
     *
     * @urlParam id int required 资源ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 资源数据
     * @responseField data.id int 资源ID
     * @responseField data.name string 资源名称
     * @responseField data.type int 资源类型:1 banner 2 友情链接 3 广告
     * @responseField data.url string 资源链接
     * @responseField data.is_target int 是否打开新窗口:1 打开 2 不打开
     * @responseField data.description string 资源描述
     * @responseField data.is_visible int 是否可见:1 可见 2 不可见
     * @responseField data.created_at string 创建时间
     */
    public function show(mixed $id): ?Model
    {
        return $this->model->firstBy($id);
    }

    /**
     * 更新资源.
     *
     * @urlParam id int required 资源ID
     *
     * @bodyParam name string required 资源名称
     * @bodyParam type string required 资源类型:1 banner 2 友情链接 3 广告
     * @bodyParam url string required 资源链接
     * @bodyParam is_target int required 是否打开新窗口:1 打开 2 不打开
     * @bodyParam description string 资源描述
     * @bodyParam is_visible int 是否可见:1 可见 2 不可见
     */
    public function update(mixed $id, Request $request): mixed
    {
        $data = $request->all();
        if (ResourceType::FRIEND_LINK->assert($data['type'])) {
            $data['content'] = '';
        }

        return $this->model->updateBy($id, $data);
    }

    /**
     * 删除资源.
     *
     * @urlParam id int required 资源ID
     */
    public function destroy(mixed $id): bool
    {
        return $this->model->deletesBy($id);
    }

    /**
     * 资源可见.
     *
     * @urlParam id int required 资源ID
     */
    public function enable(mixed $id): bool|int
    {
        return $this->model->toggleBy($id, 'is_visible');
    }
}
