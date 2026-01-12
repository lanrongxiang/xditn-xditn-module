<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Illuminate\Http\Request;
use Modules\VideoSubscription\Enums\PaymentGateway;
use Modules\VideoSubscription\Models\ReconciliationRecord;
use Modules\VideoSubscription\Services\ReconciliationService;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 对账管理
 *
 * @subgroupDescription  后台支付管理->对账管理
 */
class ReconciliationController extends Controller
{
    public function __construct(
        protected ReconciliationService $reconciliationService
    ) {
    }

    /**
     * 对账记录列表.
     *
     * @queryParam date string 对账日期（Y-m-d格式）
     * @queryParam gateway_type int 支付网关类型:1=PayPal,2=Apple Pay,3=Apple IAP
     * @queryParam status int 对账状态:2=已对账,3=有差异
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField page int 当前页
     * @responseField total int 总数
     * @responseField limit int 每页数量
     * @responseField data object[] 对账记录列表
     * @responseField data[].id int 对账ID
     * @responseField data[].reconciliation_date string 对账日期（Y-m-d）
     * @responseField data[].gateway_type int 支付网关类型:1=PayPal,2=Apple Pay,3=Apple IAP
     * @responseField data[].local_total float 本地总金额（元）
     * @responseField data[].gateway_total float 网关总金额（元）
     * @responseField data[].difference float 差异金额（元）
     * @responseField data[].local_count int 本地交易数量
     * @responseField data[].gateway_count int 网关交易数量
     * @responseField data[].local_data array 本地交易数据
     * @responseField data[].gateway_data array 网关交易数据
     * @responseField data[].difference_details array 差异详情
     * @responseField data[].status int 对账状态:2=已对账,3=有差异
     * @responseField data[].remark string|null 备注
     * @responseField data[].processed_at string|null 处理时间
     * @responseField data[].creator_id int|null 创建人ID
     * @responseField data[].created_at string 创建时间
     * @responseField data[].updated_at string 更新时间
     */
    public function index(Request $request): mixed
    {
        return ReconciliationRecord::query()
            ->when($request->input('date'), function ($query, $date) {
                $query->where('reconciliation_date', $date);
            })
            ->when($request->input('gateway_type'), function ($query, $gateway) {
                $query->where('gateway_type', $gateway);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderByDesc('reconciliation_date')
            ->orderByDesc('created_at')
            ->getList();
    }

    /**
     * 执行对账.
     *
     * 对指定日期和支付网关执行对账操作
     *
     * @bodyParam date string required 对账日期（Y-m-d格式）
     * @bodyParam gateway int required 支付网关:1=PayPal,2=Apple Pay,3=Apple IAP
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 对账结果
     * @responseField data.id int 对账记录ID
     * @responseField data.reconciliation_date string 对账日期（Y-m-d）
     * @responseField data.gateway_type int 支付网关类型:1=PayPal,2=Apple Pay,3=Apple IAP
     * @responseField data.local_total float 本地总金额（元）
     * @responseField data.gateway_total float 网关总金额（元）
     * @responseField data.difference float 差异金额（元）
     * @responseField data.local_count int 本地交易数量
     * @responseField data.gateway_count int 网关交易数量
     * @responseField data.local_data array 本地交易数据
     * @responseField data.gateway_data array 网关交易数据
     * @responseField data.difference_details array 差异详情
     * @responseField data.status int 对账状态:2=已对账,3=有差异
     * @responseField data.remark string|null 备注
     * @responseField data.processed_at string|null 处理时间
     * @responseField data.created_at string 创建时间
     * @responseField data.updated_at string 更新时间
     */
    public function reconcile(Request $request): mixed
    {
        $request->validate([
            'date' => 'required|date',
            'gateway' => 'required|integer|in:1,2,3',
        ]);

        $gateway = PaymentGateway::from($request->input('gateway'));

        return $this->reconciliationService->reconcile(
            $request->input('date'),
            $gateway
        );
    }
}
