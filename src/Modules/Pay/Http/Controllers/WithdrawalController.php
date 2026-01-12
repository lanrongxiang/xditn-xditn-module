<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Illuminate\Http\Request;
use Modules\VideoSubscription\Enums\WithdrawalStatus;
use Modules\VideoSubscription\Models\Withdrawal;
use XditnModule\Base\CatchController as Controller;
use XditnModule\Exceptions\FailedException;

/**
 * @group 管理端
 *
 * @subgroup 提现管理
 *
 * @subgroupDescription  后台支付管理->提现管理
 */
class WithdrawalController extends Controller
{
    public function __construct(
        protected readonly Withdrawal $model
    ) {
    }

    /**
     * 提现列表.
     *
     * @queryParam user_id int 用户ID
     * @queryParam withdrawal_no string 提现订单号
     * @queryParam status int 提现状态:1=待审核,2=已通过,3=已拒绝,4=已打款
     * @queryParam withdrawal_method int 提现方式:1=PayPal,2=银行转账,3=其他
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 提现记录列表
     * @responseField data[].id int 提现ID
     * @responseField data[].user_id int 用户ID
     * @responseField data[].withdrawal_no string 提现订单号
     * @responseField data[].amount float 提现金额（元）
     * @responseField data[].currency string 货币
     * @responseField data[].withdrawal_method int 提现方式:1=PayPal,2=银行转账,3=其他
     * @responseField data[].account_info string 账户信息
     * @responseField data[].account_name string 账户名称
     * @responseField data[].status int 提现状态:1=待审核,2=已通过,3=已拒绝,4=已打款
     * @responseField data[].reject_reason string|null 拒绝原因
     * @responseField data[].processed_at int|null 处理时间（时间戳）
     * @responseField data[].user object 用户信息
     * @responseField data[].user.id int 用户ID
     * @responseField data[].user.username string 用户名
     * @responseField data[].user.email string 邮箱
     * @responseField data[].user.mobile string 手机号
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->with('user');
        })->getList();
    }

    /**
     * 提现详情.
     *
     * @urlParam id int 提现ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 提现详情
     * @responseField data.id int 提现ID
     * @responseField data.user_id int 用户ID
     * @responseField data.withdrawal_no string 提现订单号
     * @responseField data.amount float 提现金额（元）
     * @responseField data.currency string 货币
     * @responseField data.withdrawal_method int 提现方式:1=PayPal,2=银行转账,3=其他
     * @responseField data.account_info string 账户信息
     * @responseField data.account_name string 账户名称
     * @responseField data.status int 提现状态:1=待审核,2=已通过,3=已拒绝,4=已打款
     * @responseField data.reject_reason string|null 拒绝原因
     * @responseField data.processed_at int|null 处理时间（时间戳）
     * @responseField data.user object 用户信息
     * @responseField data.user.id int 用户ID
     * @responseField data.user.username string 用户名
     * @responseField data.user.email string 邮箱
     * @responseField data.user.mobile string 手机号
     * @responseField data.user.avatar string 头像
     * @responseField data.user.status int 状态
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function show($id)
    {
        return $this->model->where('id', $id)
            ->with('user')
            ->first();
    }

    /**
     * 审核通过.
     *
     * 审核通过提现申请
     *
     * @urlParam id int 提现ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     */
    public function approve(int $id): mixed
    {
        $withdrawal = $this->model->findOrFail($id);

        $withdrawal->update([
            'status' => WithdrawalStatus::APPROVED,
            'processed_at' => time(),
        ]);

        return [];
    }

    /**
     * 拒绝提现.
     *
     * 拒绝提现申请，需要填写拒绝原因
     *
     * @urlParam id int 提现ID
     *
     * @bodyParam reject_reason string required 拒绝原因，最大500字符
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     */
    public function reject(Request $request, int $id): mixed
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $withdrawal = $this->model->findOrFail($id);

        $withdrawal->update([
            'status' => WithdrawalStatus::REJECTED,
            'reject_reason' => $request->input('reject_reason'),
            'processed_at' => time(),
        ]);

        return [];
    }

    /**
     * 打款完成.
     *
     * 标记提现已打款完成，提现状态必须为已通过
     *
     * @urlParam id int 提现ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     */
    public function paid(int $id): mixed
    {
        $withdrawal = $this->model->findOrFail($id);

        if ($withdrawal->status != WithdrawalStatus::APPROVED) {
            throw new FailedException(__('exception.withdrawal_status_incorrect'));
        }

        $withdrawal->update([
            'status' => WithdrawalStatus::PAID,
            'processed_at' => time(),
        ]);

        return [];
    }
}
