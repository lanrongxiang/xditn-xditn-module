<?php

declare(strict_types=1);

namespace Modules\Openapi\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Openapi\Http\Requests\UsersRequest;
use Modules\Openapi\Models\UserBalance;
use Modules\Openapi\Models\Users;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group OpenAPI接口
 *
 * @subgroup OpenAPI用户管理
 *
 * @subgroupDescription OpenAPI用户管理接口
 */
class UsersController extends Controller
{
    public function __construct(
        protected readonly Users $model
    ) {
    }

    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->addSelect([
                'balance' => UserBalance::query()->select('balance')->whereColumn('user_id', $this->model->getTable().'.id')->limit(1),
            ]);
        })->getList();
    }

    /**
     * @return mixed
     */
    public function store(UsersRequest $request)
    {
        return $this->model->storeBy($request->all());
    }

    /**
     * @return mixed
     */
    public function show($id)
    {
        return $this->model->firstBy($id);
    }

    /**
     * @return mixed
     */
    public function update($id, UsersRequest $request)
    {
        $data = $request->all();
        if (!$data['password']) {
            unset($data['password']);
        }

        return $this->model->updateBy($id, $data);
    }

    /**
     * @return mixed
     */
    public function destroy($id)
    {
        return $this->model->deleteBy($id);
    }

    /**
     * 重新生成密钥.
     */
    public function regenerate($id): mixed
    {
        return $this->model->regenerate($id);
    }

    /**
     * 充值
     *
     * @param Request $request
     * @param UserBalance $userBalanceModel
     *
     * @return mixed
     */
    public function charge(Request $request, UserBalance $userBalanceModel)
    {
        $userBalance = $userBalanceModel->where('user_id', $request->get('user_id'))->first();

        if (!$userBalance) {
            return $userBalanceModel->storeBy($request->all());
        } else {
            $userBalance->balance += $request->get('balance');

            return $userBalance->save();
        }
    }
}
