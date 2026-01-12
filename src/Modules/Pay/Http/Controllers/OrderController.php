<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Openapi\Exceptions\FailedException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Enums\RefundStatus;
use Modules\Pay\Http\Requests\OrderRefundRequest;
use Modules\Pay\Models\Order;
use Modules\Pay\Models\OrderRefund;
use Modules\Pay\Support\PayFactory;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 支付订单管理
 *
 * @subgroupDescription  后台支付管理->支付订单管理
 */
class OrderController extends Controller
{
    public function __construct(
        protected readonly Order $model,
    ) {
    }

    /**
     * 支付订单列表.
     *
     * @queryParam user_id int 用户ID
     * @queryParam out_trade_no string 第三方订单号
     * @queryParam platform int 支付平台:5=PayPal,8=PromptPay
     * @queryParam pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @queryParam refund_status int 退款状态:1=未退款,2=退款成功,3=退款失败,4=退款中,5=已拒绝
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 支付订单列表
     * @responseField data[].id string 订单ID（UUID）
     * @responseField data[].user_id int 用户ID
     * @responseField data[].out_trade_no string|null 第三方订单号
     * @responseField data[].platform int 支付平台:5=PayPal,8=PromptPay
     * @responseField data[].action int 支付动作
     * @responseField data[].amount float 订单金额（元）
     * @responseField data[].currency string 货币单位
     * @responseField data[].pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @responseField data[].refund_status int 退款状态:1=未退款,2=退款成功,3=退款失败,4=退款中,5=已拒绝
     * @responseField data[].gateway_data object|null 网关数据
     * @responseField data[].remark string|null 备注
     * @responseField data[].paid_at string|null 支付时间
     * @responseField data[].user object 用户信息
     * @responseField data[].user.id int 用户ID
     * @responseField data[].user.username string 用户名
     * @responseField data[].user.email string 邮箱
     * @responseField data[].user.mobile string 手机号
     * @responseField data[].recharge_order object|null 充值订单详情（如果是充值订单）
     * @responseField data[].recharge_order.id string 充值订单ID（UUID，与订单ID相同）
     * @responseField data[].recharge_order.activity_id int|null 活动ID
     * @responseField data[].recharge_order.coins int 充值金币数
     * @responseField data[].recharge_order.bonus_coins int 赠送金币数
     * @responseField data[].recharge_order.exchange_rate int 汇率（1单位法币=多少金币）
     * @responseField data[].subscription_order object|null 订阅订单详情（如果是订阅订单）
     * @responseField data[].subscription_order.id string 订阅订单ID（UUID，与订单ID相同）
     * @responseField data[].subscription_order.subscription_id int|null 订阅ID
     * @responseField data[].subscription_order.plan_id int 套餐ID
     * @responseField data[].subscription_order.order_type int 订单类型
     * @responseField data[].subscription_order.started_at string 开始时间
     * @responseField data[].subscription_order.expires_at string 过期时间
     * @responseField data[].subscription_order.auto_renew int 是否自动续费
     * @responseField data[].subscription_order.next_billing_at string|null 下次扣费时间
     * @responseField data[].purchase_order object|null 购买订单详情（如果是购买订单）
     * @responseField data[].purchase_order.id string 购买订单ID（UUID，与订单ID相同）
     * @responseField data[].purchase_order.episode_id int|null 剧集ID
     * @responseField data[].purchase_order.video_id int|null 视频ID
     * @responseField data[].purchase_order.coins int 消费金币数
     * @responseField data[].purchase_order.purchase_type string 购买类型
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->with(['user', 'rechargeOrder', 'subscriptionOrder', 'purchaseOrder']);
        })->getList();
    }

    /**
     * 发起退款.
     *
     * 对指定支付订单发起退款申请
     *
     * @urlParam id string 订单ID（UUID）
     *
     * @bodyParam refund_amount float required 退款金额（元），不能大于支付金额。例如：99.99元传入99.99
     * @bodyParam reason string 退款原因
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 退款订单信息
     * @responseField data.id string 退款订单ID（UUID）
     * @responseField data.order_id string 原订单ID（UUID）
     * @responseField data.refund_no string 退款单号
     * @responseField data.refund_amount float 退款金额（元）
     * @responseField data.refund_reason string|null 退款原因
     * @responseField data.refuse_reason string|null 拒绝原因
     * @responseField data.applicant_id int 申请人ID
     * @responseField data.operator_id int|null 操作人ID
     * @responseField data.refund_status int 退款状态:1=待处理,2=退款成功,3=退款失败,4=已拒绝
     * @responseField data.refunded_at string|null 退款时间
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function refund($id, OrderRefundRequest $request): mixed
    {
        // @var Order $order
        $order = $this->model->find($id);

        if (!$order) {
            throw new FailedException('订单不存在');
        }

        $refundAmount = $request->getRefundAmount();
        $reason = $request->getReason();

        if ($refundAmount > $order->amount) {
            throw new FailedException('退款金额不能大于支付金额');
        }

        Log::channel('payment')->info('开始处理退款申请', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'refund_amount' => $refundAmount,
            'order_amount' => $order->amount,
            'applicant_id' => $this->getLoginUserId(),
        ]);

        try {
            // 使用事务和锁定确保数据一致性
            $orderRefund = DB::transaction(function () use ($order, $refundAmount, $reason) {
                // 锁定订单（防止并发操作）
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
                if (!$lockedOrder) {
                    throw new FailedException('订单不存在');
                }

                $lockedOrder->refund_status = RefundStatus::PENDING;
                $lockedOrder->save();

                /** @var \Modules\Pay\Support\Pay $payInstance */
                $platformEnum = $lockedOrder->platform instanceof PayPlatform ? $lockedOrder->platform : PayPlatform::from((int) $lockedOrder->platform);
                $payInstance = PayFactory::make($platformEnum);

                // OrderRefund 模型的 refund_amount 访问器会自动将元转换为分存储
                return OrderRefund::create([
                    'id' => Str::uuid(),
                    'order_id' => $lockedOrder->id,
                    'refund_no' => $payInstance->createRefundOrderNo(),
                    'refund_amount' => $refundAmount, // 金额（元），访问器会自动转换为分存储
                    'refund_reason' => $reason,
                    'applicant_id' => $this->getLoginUserId(),
                    'refund_status' => RefundStatus::PENDING->value,
                ]);
            });

            Log::channel('payment')->info('退款申请创建成功', [
                'refund_id' => $orderRefund->id,
                'order_id' => $order->id,
                'refund_amount' => $refundAmount,
            ]);

            return [$orderRefund];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('退款申请创建失败', [
                'order_id' => $order->id,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
