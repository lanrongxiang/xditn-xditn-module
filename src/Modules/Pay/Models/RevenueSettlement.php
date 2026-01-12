<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Common\Support\Casts\AmountCast;
use XditnModule\Base\CatchModel;

/**
 * @property $id
 * @property $settlement_date
 * @property $recharge_count
 * @property $recharge_amount
 * @property $subscription_count
 * @property $subscription_revenue
 * @property $purchase_count
 * @property $purchase_revenue
 * @property $renewal_count
 * @property $renewal_revenue
 * @property $refund_count
 * @property $refund_amount
 * @property $total_revenue
 * @property $net_revenue
 * @property $currency
 * @property $gateway_breakdown
 * @property $status
 * @property $confirmed_at
 * @property $created_at
 * @property $updated_at
 */
class RevenueSettlement extends CatchModel
{
    use HasUuids;

    protected $table = 'pay_revenue_settlements';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * 禁用软删除（表没有 deleted_at 字段）
     * CatchModel 默认包含 SoftDeletes，通过重写 bootSoftDeletes 来禁用.
     */
    public static function bootSoftDeletes(): void
    {
        // 不执行任何操作，禁用软删除功能
    }

    protected $fillable = [
        'id',
        'settlement_date',
        'recharge_count',
        'recharge_amount',
        'subscription_count',
        'subscription_revenue',
        'purchase_count',
        'purchase_revenue',
        'renewal_count',
        'renewal_revenue',
        'refund_count',
        'refund_amount',
        'total_revenue',
        'net_revenue',
        'currency',
        'gateway_breakdown',
        'status',
        'confirmed_at',
    ];

    protected array $fields = [
        'id',
        'settlement_date',
        'recharge_count',
        'recharge_amount',
        'subscription_count',
        'subscription_revenue',
        'purchase_count',
        'purchase_revenue',
        'renewal_count',
        'renewal_revenue',
        'refund_count',
        'refund_amount',
        'total_revenue',
        'net_revenue',
        'currency',
        'status',
        'confirmed_at',
        'created_at',
    ];

    protected $casts = [
        'gateway_breakdown' => 'array',
        'settlement_date' => 'date:Y-m-d', // 明确指定日期格式，避免被转换为时间戳
        'confirmed_at' => 'datetime:Y-m-d H:i:s', // 明确指定 datetime 格式，避免被转换为时间戳
        // 金额字段统一使用 AmountCast 处理（分转元）
        'recharge_amount' => AmountCast::class,
        'subscription_revenue' => AmountCast::class,
        'purchase_revenue' => AmountCast::class,
        'renewal_revenue' => AmountCast::class,
        'refund_amount' => AmountCast::class,
        'total_revenue' => AmountCast::class,
        'net_revenue' => AmountCast::class,
    ];

}
