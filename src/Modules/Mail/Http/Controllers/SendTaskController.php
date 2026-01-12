<?php

declare(strict_types=1);

namespace Modules\Mail\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Modules\Mail\Http\Requests\SendTaskRequest;
use Modules\Mail\Models\MailTemplate;
use Modules\Mail\Models\SendTask;
use XditnModule\Base\CatchController as Controller;

/**
 * @class SendTaskController
 */
class SendTaskController extends Controller
{
    /**
     * @param SendTask $model
     */
    public function __construct(
        protected readonly SendTask $model,
    ) {
    }

    /**
     * 列表.
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->addSelect([
                'template' => MailTemplate::query()
                    ->whereColumn('id', $this->model->getTable().'.template_id')
                    ->select(DB::raw('name')),
            ]);
        })->getList();
    }

    /**
     * 保存数据.
     *
     * @param SendTaskRequest $request
     *
     * @return mixed
     */
    public function store(SendTaskRequest $request): mixed
    {
        $data = $request->all();
        $data['recipients_num'] = count($data['recipients']);
        $data['remark'] = $data['remark'] ?? '';
        $data['send_at'] = $data['send_at'] ? strtotime($data['scheduled_at']) : 0;

        return $this->model->storeBy($data);
    }

    /**
     * 展示数据.
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function show(mixed $id): mixed
    {
        return $this->model->firstBy($id, columns: $this->model->getForm());
    }

    /**
     * 更新数据.
     *
     * @param mixed $id
     * @param SendTaskRequest $request
     *
     * @return mixed
     */
    public function update(mixed $id, SendTaskRequest $request): mixed
    {
        $data = $request->all();
        $data['recipients_num'] = count($data['recipients']);
        $data['remark'] = $data['remark'] ?? '';
        $data['send_at'] = $data['send_at'] ? strtotime($data['scheduled_at']) : 0;

        return $this->model->updateBy($id, $data);
    }

    /**
     * 删除数据.
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function destroy(mixed $id): mixed
    {
        return $this->model->deleteBy($id);
    }
}
