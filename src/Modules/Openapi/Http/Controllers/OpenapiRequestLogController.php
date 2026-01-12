<?php

declare(strict_types=1);

namespace Modules\Openapi\Http\Controllers;

use Modules\Openapi\Models\OpenapiRequestLog;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group OpenAPI接口
 *
 * @subgroup OpenAPI请求日志
 *
 * @subgroupDescription OpenAPI请求日志管理
 */
class OpenapiRequestLogController extends Controller
{
    public function __construct(
        protected readonly OpenapiRequestLog $model
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
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->model->deleteBy($id);
    }
}
