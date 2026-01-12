<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ai\Models\KnowledgeFiles;
use XditnModule\Base\CatchController as Controller;

/**
 * @class AiKnowledgeFilesController
 */
class KnowledgeFilesController extends Controller
{
    public function __construct(
        protected readonly KnowledgeFiles $model,
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

    public function parseFiles(Request $request)
    {
    }
}
