<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Modules\Pay\Models\Transaction;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 交易记录管理
 *
 * @subgroupDescription  后台支付管理->交易记录管理
 */
class TransactionController extends Controller
{
    public function __construct(
        protected readonly Transaction $model
    ) {
    }

    /**
     * 交易记录列表.
     *
     * @queryParam user_id int 用户ID
     * @queryParam type string 交易类型
     * @queryParam currency_type string 货币类型:fiat=法币,coin=金币
     * @queryParam order_id string 订单ID（UUID）
     * @queryParam transaction_no string 交易单号
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 交易记录列表
     * @responseField data[].id string 交易ID（UUID）
     * @responseField data[].transaction_no string 交易单号
     * @responseField data[].user_id int 用户ID
     * @responseField data[].type string 交易类型:recharge=充值,withdraw=提现,refund=退款,payment=支付,coin_recharge=金币充值,coin_consume=金币消费,coin_refund=金币退款,coin_bonus=金币赠送
     * @responseField data[].currency_type string 货币类型:fiat=法币,coin=金币
     * @responseField data[].amount float 金额（元）
     * @responseField data[].currency string 货币单位
     * @responseField data[].direction int 方向:1=收入,2=支出
     * @responseField data[].balance_before int 操作前余额
     * @responseField data[].balance_after int 操作后余额
     * @responseField data[].order_id string|null 关联订单ID（UUID）
     * @responseField data[].order_no string|null 订单号
     * @responseField data[].related_type string|null 关联类型
     * @responseField data[].related_id string|null 关联ID
     * @responseField data[].description string 描述
     * @responseField data[].extra_data object|null 扩展数据
     * @responseField data[].transaction_at string 交易时间
     * @responseField data[].user object 用户信息
     * @responseField data[].user.id int 用户ID
     * @responseField data[].user.username string 用户名
     * @responseField data[].user.email string 邮箱
     * @responseField data[].order object|null 关联订单信息
     * @responseField data[].order.id string 订单ID（UUID）
     * @responseField data[].order.out_trade_no string|null 第三方订单号
     * @responseField data[].order.amount float 订单金额（元）
     * @responseField data[].order.pay_status int 支付状态
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->with(['user', 'order']);
        })->getList();
    }

    /**
     * 交易记录详情.
     *
     * @urlParam id string 交易ID（UUID）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 交易详情
     * @responseField data.id string 交易ID（UUID）
     * @responseField data.transaction_no string 交易单号
     * @responseField data.user_id int 用户ID
     * @responseField data.type string 交易类型
     * @responseField data.currency_type string 货币类型:fiat=法币,coin=金币
     * @responseField data.amount float 金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.currency string 货币单位
     * @responseField data.direction int 方向:1=收入,2=支出
     * @responseField data.balance_before int 操作前余额
     * @responseField data.balance_after int 操作后余额
     * @responseField data.order_id string|null 关联订单ID（UUID）
     * @responseField data.order_no string|null 订单号
     * @responseField data.related_type string|null 关联类型
     * @responseField data.related_id string|null 关联ID
     * @responseField data.description string 描述
     * @responseField data.extra_data object|null 扩展数据
     * @responseField data.transaction_at string 交易时间
     * @responseField data.user object 用户信息
     * @responseField data.user.id int 用户ID
     * @responseField data.user.username string 用户名
     * @responseField data.user.email string 邮箱
     * @responseField data.order object|null 关联订单信息
     * @responseField data.order.id string 订单ID（UUID）
     * @responseField data.order.user_id int 用户ID
     * @responseField data.order.out_trade_no string|null 第三方订单号
     * @responseField data.order.platform int 支付平台
     * @responseField data.order.amount float 订单金额（元），数据库存储为分，通过访问器自动转换为元返回
     * @responseField data.order.currency string 货币单位
     * @responseField data.order.pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @responseField data.order.refund_status int 退款状态:1=未退款,2=退款成功,3=退款失败
     * @responseField data.order.paid_at string|null 支付时间
     * @responseField data.order.created_at string 创建时间
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function show($id)
    {
        return $this->model->where('id', $id)
            ->with(['user', 'order'])
            ->first();
    }
}
