<?php

declare(strict_types=1);

namespace Modules\Domain\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Domain\Models\Domains;
use XditnModule\Base\CatchController as Controller;
use XditnModule\Exceptions\FailedException;

class DomainsController extends Controller
{
    public function __construct(
        protected readonly Domains $model
    ) {
    }

    /**
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        if ($this->model->where('name', $name = $request->get('name'))->exists()) {
            throw new FailedException("{$name} 域名已存在");
        }

        return DB::transaction(function () use ($request) {
            $this->model->storeBy($request->all());
        });
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function show($id)
    {
        return $this->model->firstBy($id);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return mixed
     */
    public function update($id, Request $request)
    {
        return $this->model->updateBy($id, $request->all());
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->model->deleteBy($id);
    }
}
