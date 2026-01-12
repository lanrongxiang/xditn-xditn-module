<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Pay\Enums\PayAction;
use Modules\Pay\Enums\PayPlatform;
use Modules\Pay\Enums\PayStatus;
use Modules\Pay\Enums\RefundStatus;
use XditnModule\Base\XditnModuleModel;

/**
 * @property $id
 * @property $user_id
 * @property $out_trade_no
 * @property $platform
 * @property $action
 * @property $amount
 * @property $currency
 * @property $pay_status
 * @property $refund_status
 * @property $gateway_data
 * @property $remark
 * @property $paid_at
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class Order extends XditnModuleModel
{
    use HasUuids;

    protected $table = 'pay_orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'out_trade_no',
        'platform',
        'action',
        'amount',
        'currency',
        'pay_status',
        'refund_status',
        'gateway_data',
        'remark',
        'paid_at',
    ];

    protected array $fields = [
        'id',
        'user_id',
        'out_trade_no',
        'platform',
        'action',
        'amount',
        'currency',
        'pay_status',
        'refund_status',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    protected array $form = [
        'user_id',
        'out_trade_no',
        'platform',
        'action',
        'amount',
        'currency',
        'pay_status',
        'refund_status',
        'gateway_data',
        'remark',
    ];

    protected $casts = [
        'platform' => PayPlatform::class,
        'action' => PayAction::class,
        'pay_status' => PayStatus::class,
        'refund_status' => RefundStatus::class,
        'gateway_data' => 'array',
        'paid_at' => 'datetime',
    ];

    public array $searchable = [
        'out_trade_no' => 'like',
        'user_id' => '=',
        'platform' => '=',
        'pay_status' => '=',
        'refund_status' => '=',
    ];

    /**
     * 金额转换（分转元）.
     */
    protected function amount(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) ($value * 100)
        );
    }

    /**
     * paid_at 字段转换
     * 该字段是 timestamp 类型（datetime），需要保持 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    protected function paidAt(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? (is_numeric($value) ? date('Y-m-d H:i:s', (int) $value) : $value) : null,
            set: function ($value) {
                // 如果是 Carbon 实例或 DateTime，转换为 datetime 字符串
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                }
                // 如果是 Unix 时间戳，转换为 datetime 字符串
                if (is_numeric($value)) {
                    return date('Y-m-d H:i:s', (int) $value);
                }

                // 如果已经是字符串，直接返回
                return $value;
            }
        );
    }

    /**
     * 是否待支付.
     */
    public function isPayPending(): bool
    {
        return $this->pay_status == PayStatus::PENDING;
    }

    /**
     * 是否支付成功
     */
    public function isPaySuccess(): bool
    {
        return $this->pay_status == PayStatus::SUCCESS;
    }

    /**
     * 是否支付失败.
     */
    public function isPayFail(): bool
    {
        return $this->pay_status == PayStatus::FAILED;
    }

    /**
     * 是否支付超时.
     */
    public function isPayTimeout(): bool
    {
        return $this->pay_status == PayStatus::TIMEOUT;
    }

    /**
     * 是否退款成功
     */
    public function isRefundSuccess(): bool
    {
        return $this->refund_status == RefundStatus::SUCCESS;
    }

    /**
     * 是否退款失败.
     */
    public function isRefundFail(): bool
    {
        return $this->refund_status == RefundStatus::FAILED;
    }

    /**
     * 是否待退款.
     */
    public function isRefundPending(): bool
    {
        return $this->refund_status == RefundStatus::PENDING;
    }

    /**
     * 是否未退款.
     */
    public function isRefundNone(): bool
    {
        return $this->refund_status == RefundStatus::NONE;
    }

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Member\Models\Members::class, 'user_id');
    }

    /**
     * 关联充值订单.
     */
    public function rechargeOrder(): HasOne|Order
    {
        return $this->hasOne(RechargeOrder::class, 'id', 'id');
    }

    /**
     * 关联订阅订单.
     */
    public function subscriptionOrder(): HasOne|Order
    {
        return $this->hasOne(SubscriptionOrder::class, 'id', 'id');
    }

    /**
     * 关联购买订单.
     */
    public function purchaseOrder(): HasOne|Order
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'id');
    }

    /**
     * 关联退款记录.
     */
    public function refunds(): HasMany|Order
    {
        return $this->hasMany(OrderRefund::class, 'order_id', 'id');
    }

    /**
     * 关联资金明细.
     */
    public function transactions(): HasMany|Order
    {
        return $this->hasMany(Transaction::class, 'order_id', 'id');
    }
}
