<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Modules\Ai\Http\Requests\AiKnowledgeBaseRequest as Request;
use Modules\Ai\Models\KnowledgeBase;
use XditnModule\Base\CatchController as Controller;

/**
 * @class AiKnowledgeBaseController
 */
class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected readonly KnowledgeBase $model,
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
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * 删除数据.
     */
    public function destroy(mixed $id): mixed
    {
        return $this->model->deleteBy($id);
    }

    public function enable($id): bool
    {
        return $this->model->toggleBy($id);
    }
}
