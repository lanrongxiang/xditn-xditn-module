<?php

namespace Modules\Domain\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Domain\Models\Domains;
use Modules\Domain\Support\Request\Request as DomainRequest;
use XditnModule\Base\CatchController as Controller;

class DomainRecordsController extends Controller
{
    protected DomainRequest $model;

    protected string $domain;

    public function __construct(Request $request)
    {
        $domainId = trim($request->get('id'), '/');
        $domainModel = Domains::find($domainId);
        if ($domainModel) {
            $this->domain = $domainModel->name;
            $this->model = $domainModel->api();
        }
    }

    public function index(Request $request): mixed
    {
        [$records, $total] = $this->model->getList($this->domain, $request->get('page') - 1, $request->get('limit'));

        return new LengthAwarePaginator($records, $total, $request->get('limit'), $request->get('page'));
    }

    public function store(Request $request): mixed
    {
        return $this->model->store($request->except('id'));
    }

    /**
     * @return mixed
     */
    public function show($id)
    {
        return $this->model->show($id, $this->domain);
    }

    /**
     * @return mixed
     */
    public function update($id, Request $request)
    {
        return $this->model->update($id, $request->all());
    }

    /**
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->model->destroy($id, $this->domain);
    }
}
