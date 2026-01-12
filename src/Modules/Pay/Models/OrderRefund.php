<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Pay\Enums\RefundStatus;
use XditnModule\Base\CatchModel;

/**
 * @property $id
 * @property $order_id
 * @property $refund_no
 * @property $refund_amount
 * @property $refund_reason
 * @property $refuse_reason
 * @property $applicant_id
 * @property $operator_id
 * @property $refund_status
 * @property $refunded_at
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class OrderRefund extends CatchModel
{
    use HasUuids;

    protected $table = 'pay_order_refunds';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'refund_no',
        'refund_amount',
        'refund_reason',
        'refuse_reason',
        'applicant_id',
        'operator_id',
        'refund_status',
        'refunded_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $fields = [
        'id',
        'order_id',
        'refund_no',
        'refund_amount',
        'refund_reason',
        'refund_status',
        'refunded_at',
        'created_at',
        'updated_at',
    ];

    protected array $form = [
        'order_id',
        'refund_no',
        'refund_amount',
        'refund_reason',
        'refund_status',
    ];

    protected $casts = [
        'refund_status' => RefundStatus::class,
    ];

    /**
     * refunded_at 字段转换
     * 该字段是 timestamp 类型（datetime），需要保持 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    protected function refundedAt(): Attribute
    {
        return new Attribute(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }
                if (is_numeric($value)) {
                    return \Carbon\Carbon::createFromTimestamp((int) $value);
                }
                if (is_string($value)) {
                    return \Carbon\Carbon::parse($value);
                }

                return $value instanceof \DateTimeInterface ? \Carbon\Carbon::instance($value) : $value;
            },
            set: function ($value) {
                if ($value === null) {
                    return null;
                }
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                }
                if (is_numeric($value)) {
                    return date('Y-m-d H:i:s', (int) $value);
                }

                return $value;
            }
        );
    }

    public array $searchable = [
        'refund_no' => 'like',
        'order_id' => '=',
        'refund_status' => '=',
    ];

    /**
     * 退款金额转换（分转元）.
     */
    protected function refundAmount(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) ($value * 100)
        );
    }

    /**
     * 关联订单.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 是否待退款.
     */
    public function isPending(): bool
    {
        return $this->refund_status == RefundStatus::PENDING;
    }

    /**
     * 是否退款成功
     */
    public function isSuccess(): bool
    {
        return $this->refund_status == RefundStatus::SUCCESS;
    }

    /**
     * 是否退款失败.
     */
    public function isFailed(): bool
    {
        return $this->refund_status == RefundStatus::FAILED;
    }

    /**
     * 是否拒绝退款.
     */
    public function isRefused(): bool
    {
        return $this->refund_status == RefundStatus::REFUSE;
    }
}
