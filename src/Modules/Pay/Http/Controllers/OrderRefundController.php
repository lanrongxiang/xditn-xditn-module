<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Openapi\Exceptions\FailedException;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Enums\RefundStatus;
use Modules\Pay\Gateways\PayFactory;
use Modules\Pay\Http\Requests\OrderRefundAgreeRequest;
use Modules\Pay\Models\Order;
use Modules\Pay\Models\OrderRefund;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 退款订单管理
 *
 * @subgroupDescription  后台支付管理->退款订单管理
 */
class OrderRefundController extends Controller
{
    public function __construct(
        protected readonly OrderRefund $model,
    ) {
    }

    /**
     * 退款订单列表.
     *
     * @queryParam order_id string 订单ID（UUID）
     * @queryParam refund_no string 退款单号
     * @queryParam refund_status int 退款状态:1=待处理,2=退款成功,3=退款失败,4=已拒绝
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 退款订单列表
     * @responseField data[].id string 退款订单ID（UUID）
     * @responseField data[].order_id string 原订单ID（UUID）
     * @responseField data[].refund_no string 退款单号
     * @responseField data[].refund_amount float 退款金额（元）
     * @responseField data[].refund_reason string|null 退款原因
     * @responseField data[].refuse_reason string|null 拒绝原因
     * @responseField data[].applicant_id int 申请人ID
     * @responseField data[].operator_id int|null 操作人ID
     * @responseField data[].refund_status int 退款状态:1=待处理,2=退款成功,3=退款失败,4=已拒绝
     * @responseField data[].refunded_at string|null 退款时间
     * @responseField data[].order object 关联订单信息
     * @responseField data[].order.id string 订单ID（UUID）
     * @responseField data[].order.user_id int 用户ID
     * @responseField data[].order.out_trade_no string|null 第三方订单号
     * @responseField data[].order.platform int 支付平台
     * @responseField data[].order.amount float 订单金额（元）
     * @responseField data[].order.currency string 货币单位
     * @responseField data[].order.pay_status int 支付状态:1=待支付,2=支付成功,3=支付失败,4=超时未支付
     * @responseField data[].order.refund_status int 退款状态
     * @responseField data[].order.paid_at string|null 支付时间
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function index(): mixed
    {
        return $this->model->setBeforeGetList(function ($query) {
            return $query->with('order');
        })->getList();
    }

    /**
     * 同意/拒绝退款.
     *
     * 审核退款申请，同意则执行退款，拒绝则填写拒绝原因
     *
     * @urlParam id string 退款订单ID（UUID）
     *
     * @bodyParam is_agree boolean required 是否同意:true=同意,false=拒绝
     * @bodyParam refuse_reason string 拒绝原因（拒绝时必填）
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object|null 退款结果（同意时返回）
     * @responseField data.refund_id string 退款ID
     * @responseField data.status string 退款状态
     * @responseField data.message string 退款消息
     */
    public function agree($id, OrderRefundAgreeRequest $request)
    {
        $isAgree = $request->isAgree();
        $refuseReason = $request->getRefuseReason();

        // 使用事务和锁定确保数据一致性
        return DB::transaction(function () use ($id, $isAgree, $refuseReason) {
            // 锁定退款订单（防止并发操作）
            $orderRefund = OrderRefund::where('id', $id)->lockForUpdate()->first();
            if (!$orderRefund) {
                throw new FailedException('退款订单不存在');
            }

            // 锁定原订单（防止并发操作）
            $order = Order::where('id', $orderRefund->order_id)->lockForUpdate()->first();
            if (!$order) {
                throw new FailedException('原订单不存在');
            }

            Log::channel('payment')->info('开始处理退款审核', [
                'refund_id' => $orderRefund->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'refund_amount' => $orderRefund->refund_amount,
                'is_agree' => $isAgree,
                'operator_id' => $this->getLoginUserId(),
            ]);

            // 同意退款
            if ($isAgree) {
                try {
                    $platformEnum = $order->platform instanceof PayPlatform ? $order->platform : PayPlatform::from((int) $order->platform);
                    $refundResult = PayFactory::make($platformEnum)->refund([
                        'out_trade_no' => $order->out_trade_no,
                        'amount' => $orderRefund->getRawOriginal('refund_amount'), // 获取原始值（分）
                        'action' => $order->action,
                    ]);

                    // 更新退款状态
                    $orderRefund->refund_status = RefundStatus::SUCCESS;
                    $orderRefund->refunded_at = now();
                    $orderRefund->operator_id = $this->getLoginUserId();
                    $orderRefund->save();

                    $order->refund_status = RefundStatus::SUCCESS;
                    $order->save();

                    Log::channel('payment')->info('退款处理成功', [
                        'refund_id' => $orderRefund->id,
                        'order_id' => $order->id,
                        'refund_amount' => $orderRefund->refund_amount,
                        'refund_result' => $refundResult,
                    ]);

                    return $refundResult;
                } catch (\Throwable $e) {
                    // 更新退款状态为失败
                    $orderRefund->refund_status = RefundStatus::FAILED;
                    $orderRefund->operator_id = $this->getLoginUserId();
                    $orderRefund->save();

                    $order->refund_status = RefundStatus::FAILED;
                    $order->save();

                    Log::channel('payment')->error('退款处理失败', [
                        'refund_id' => $orderRefund->id,
                        'order_id' => $order->id,
                        'refund_amount' => $orderRefund->refund_amount,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    throw $e;
                }
            }

            // 拒绝退款
            if (!$refuseReason) {
                throw new FailedException('拒绝原因不能为空');
            }

            $orderRefund->refund_status = RefundStatus::REFUSE;
            $orderRefund->refuse_reason = $refuseReason;
            $orderRefund->operator_id = $this->getLoginUserId();
            $orderRefund->save();

            $order->refund_status = RefundStatus::REFUSE;
            $order->save();

            Log::channel('payment')->info('退款申请已拒绝', [
                'refund_id' => $orderRefund->id,
                'order_id' => $order->id,
                'refuse_reason' => $refuseReason,
            ]);

            return $orderRefund;
        });
    }
}
