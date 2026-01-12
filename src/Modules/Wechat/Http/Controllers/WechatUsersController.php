<?php

declare(strict_types=1);

namespace Modules\Wechat\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Wechat\Models\WechatUsers;
use Modules\Wechat\Support\Official\Sync\Users;
use XditnModule\Base\XditnModuleController as Controller;

class WechatUsersController extends Controller
{
    public function __construct(
        protected readonly WechatUsers $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->model->deleteBy($id);
    }

    /**
     * @return mixed
     */
    public function remark($id, $remark)
    {
        return $this->model->remark($id, $remark);
    }

    /**
     * @return mixed
     */
    public function block($id)
    {
        return $this->model->block($id);
    }

    /**
     * @return mixed
     */
    public function tag($id, Request $request)
    {
        return $this->model->tag($id, $request->post());
    }

    /**
     * @return mixed
     */
    public function sync(Users $users)
    {
        $users->start();
    }
}
