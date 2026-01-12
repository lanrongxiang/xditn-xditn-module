<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Ai\Models\AiProviders;
use XditnModule\Base\CatchController as Controller;
use XditnModule\Exceptions\FailedException;

/**
 * @class AiProvidersController
 */
class ProvidersController extends Controller
{
    public function __construct(
        protected readonly AiProviders $model,
    ) {
    }

    /**
     * 列表.
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->with(['models' => function ($query) {
                $query->select(['id', 'name', 'provider_id', 'display_name', 'max_token', 'is_support_image', 'status'])
                    ->orderBy('status')
                    ->orderByDesc('id');
            }]);
        })->setPaginate(false)->getList();
    }

    /**
     * 保存数据.
     */
    public function store(Request $request): mixed
    {

        if ($this->model->where('title', $name = $request->get('title'))->exists()) {
            throw new FailedException("{$name} 提供商已存在");
        }

        return $this->model->storeBy($request->all());
    }

    /**
     * 展示数据.
     */
    public function show(mixed $id): mixed
    {
        return $this->model->firstBy($id);
    }

    /**
     * 更新数据.
     */
    public function update(mixed $id, Request $request): mixed
    {
        if ($this->model->where('title', $name = $request->get('title'))->where('id', '!=', $id)->exists()) {
            throw new FailedException("{$name} 提供商已存在");
        }

        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除数据.
     */
    public function destroy(mixed $id): mixed
    {
        $provider = $this->model->where('id', $id)
            ->with(['models' => function ($query) {
                $query->withCount(['robots']);
            }])->first();

        foreach ($provider->models as $model) {
            if ($model['robots_count'] > 0) {
                throw new FailedException("模型{$model['name']}已被智能体占用，无法删除");
            }
        }

        return DB::transaction(function () use ($provider) {
            $provider->delete();

            $provider->models()->delete();
        });
    }

    public function enable($id): bool
    {
        return $this->model->toggleBy($id);
    }
}
