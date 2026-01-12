<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ai\Models\AiModels;
use XditnModule\Base\CatchController as Controller;
use XditnModule\Exceptions\FailedException;

/**
 * @class AiModelsController
 */
class ModelsController extends Controller
{
    public function __construct(
        protected readonly AiModels $model,
    ) {
    }

    /**
     * 列表.
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 保存数据.
     */
    public function store(Request $request): mixed
    {
        if ($this->model->where('name', $name = $request->get('name'))->exists()) {
            throw new FailedException("{$name} 模型已存在");
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
        if ($this->model->where('name', $name = $request->get('name'))->whereNot('id', $id)->exists()) {
            throw new FailedException("{$name} 模型已存在");
        }

        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除数据.
     */
    public function destroy(mixed $id): mixed
    {
        $robot = $this->model->robots()->first();
        if ($robot) {
            throw new FailedException("模型已被智能体{$robot->name}占用，无法删除");
        }

        return $this->model->deleteBy($id);
    }

    /**
     * @return bool
     */
    public function enable($id)
    {
        return $this->model->toggleBy($id);
    }
}
