<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Modules\Ai\Http\Requests\AiChatBotsRequest as Request;
use Modules\Ai\Models\ChatBots;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @class AiChatBotsController
 */
class ChatBotsController extends Controller
{
    public function __construct(
        protected readonly ChatBots $model,
    ) {
    }

    /**
     * 列表.
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->with(['models' => fn ($query) => $query->select(['name'])]);
        })->getList();
    }

    /**
     * 保存数据.
     */
    public function store(Request $request): mixed
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * 展示数据.
     */
    public function show(mixed $id): mixed
    {
        return $this->model->setAfterFirstBy(function ($model) {
            return $model->setAttribute('models', $model->models()->get()->pluck('id'));
        })->firstBy($id);
    }

    /**
     * 更新数据.
     */
    public function update(mixed $id, Request $request): mixed
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除数据.
     */
    public function destroy(mixed $id): mixed
    {
        return $this->model->deleteBy($id);
    }

    /**
     * 启用.
     */
    public function enable($id)
    {
        return $this->model->toggleBy($id);
    }
}
