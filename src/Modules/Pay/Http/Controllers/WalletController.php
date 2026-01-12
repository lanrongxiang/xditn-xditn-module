<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Modules\Pay\Models\Order;
use Modules\VideoSubscription\Models\UserWallet;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 钱包管理
 *
 * @subgroupDescription  后台支付管理->钱包管理
 */
class WalletController extends Controller
{
    public function __construct(
        protected readonly UserWallet $model
    ) {
    }

    /**
     * 钱包列表.
     *
     * 获取所有用户的钱包信息列表
     *
     * @queryParam user_id int 用户ID
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 钱包列表
     * @responseField data[].id int 钱包ID
     * @responseField data[].user_id int 用户ID
     * @responseField data[].coins int 当前金币余额
     * @responseField data[].total_recharged int 累计充值金币
     * @responseField data[].total_consumed int 累计消费金币
     * @responseField data[].frozen_coins int 冻结金币
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
     * 钱包详情.
     *
     * 获取指定用户的钱包详细信息
     *
     * @urlParam id int 钱包ID
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 钱包详情
     * @responseField data.id int 钱包ID
     * @responseField data.user_id int 用户ID
     * @responseField data.coins int 当前金币余额
     * @responseField data.total_recharged int 累计充值金币
     * @responseField data.total_consumed int 累计消费金币
     * @responseField data.frozen_coins int 冻结金币
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
    public function show($id): mixed
    {
        return $this->model->where('id', $id)
            ->with('user')
            ->first();
    }

    /**
     * 充值订单列表.
     *
     * 获取所有用户的充值订单列表
     *
     * @queryParam user_id int 用户ID
     * @queryParam order_no string 订单号（UUID）
     * @queryParam pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @queryParam platform int 支付平台:5=PayPal,8=PromptPay
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 充值订单列表
     * @responseField data[].id string 订单ID（UUID）
     * @responseField data[].user_id int 用户ID
     * @responseField data[].out_trade_no string|null 第三方订单号
     * @responseField data[].platform int 支付平台:5=PayPal,8=PromptPay
     * @responseField data[].action int 支付动作
     * @responseField data[].amount float 充值金额（元）
     * @responseField data[].currency string 货币单位
     * @responseField data[].pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @responseField data[].refund_status int 退款状态:1=未退款,2=退款成功,3=退款失败
     * @responseField data[].gateway_data object|null 网关数据
     * @responseField data[].remark string|null 备注
     * @responseField data[].paid_at string|null 支付时间
     * @responseField data[].user object 用户信息
     * @responseField data[].user.id int 用户ID
     * @responseField data[].user.username string 用户名
     * @responseField data[].user.email string 邮箱
     * @responseField data[].recharge_order object 充值订单详情
     * @responseField data[].recharge_order.id string 充值订单ID（UUID，与订单ID相同）
     * @responseField data[].recharge_order.activity_id int|null 活动ID
     * @responseField data[].recharge_order.coins int 充值金币数
     * @responseField data[].recharge_order.bonus_coins int 赠送金币数
     * @responseField data[].recharge_order.exchange_rate int 汇率（1单位法币=多少金币）
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function rechargeOrders(): mixed
    {
        return (new Order())->setBeforeGetList(function ($query) {
            return $query->whereHas('rechargeOrder')
                ->with(['user', 'rechargeOrder']);
        })->getList();
    }

    /**
     * 充值订单详情.
     *
     * 获取指定充值订单的详细信息
     *
     * @urlParam id string 订单ID（UUID）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 订单详情
     * @responseField data.id string 订单ID（UUID）
     * @responseField data.user_id int 用户ID
     * @responseField data.out_trade_no string|null 第三方订单号
     * @responseField data.platform int 支付平台:5=PayPal,8=PromptPay
     * @responseField data.action int 支付动作
     * @responseField data.amount float 充值金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.currency string 货币单位
     * @responseField data.pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @responseField data.refund_status int 退款状态:1=未退款,2=退款成功,3=退款失败
     * @responseField data.gateway_data object|null 网关数据
     * @responseField data.remark string|null 备注
     * @responseField data.paid_at string|null 支付时间
     * @responseField data.user object 用户信息
     * @responseField data.user.id int 用户ID
     * @responseField data.user.username string 用户名
     * @responseField data.user.email string 邮箱
     * @responseField data.user.mobile string 手机号
     * @responseField data.recharge_order object 充值订单详情
     * @responseField data.recharge_order.id string 充值订单ID（UUID，与订单ID相同）
     * @responseField data.recharge_order.activity_id int|null 活动ID
     * @responseField data.recharge_order.coins int 充值金币数
     * @responseField data.recharge_order.bonus_coins int 赠送金币数
     * @responseField data.recharge_order.exchange_rate int 汇率（1单位法币=多少金币）
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function rechargeOrder($id): mixed
    {
        return (new Order())->whereHas('rechargeOrder')
            ->with(['user', 'rechargeOrder'])
            ->firstBy($id);
    }
}
