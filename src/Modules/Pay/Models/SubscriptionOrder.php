<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use XditnModule\Base\CatchModel;

/**
 * @property $id
 * @property $subscription_id
 * @property $plan_id
 * @property $order_type
 * @property $started_at
 * @property $expires_at
 * @property $auto_renew
 * @property $next_billing_at
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class SubscriptionOrder extends CatchModel
{
    use HasUuids;

    protected $table = 'pay_subscription_orders';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'subscription_id',
        'plan_id',
        'order_type',
        'started_at',
        'expires_at',
        'auto_renew',
        'next_billing_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $fields = [
        'id',
        'subscription_id',
        'plan_id',
        'order_type',
        'started_at',
        'expires_at',
        'auto_renew',
        'next_billing_at',
        'created_at',
        'updated_at',
    ];

    protected array $form = [
        'id',
        'subscription_id',
        'plan_id',
        'order_type',
        'started_at',
        'expires_at',
        'auto_renew',
        'next_billing_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'next_billing_at' => 'datetime',
    ];

    /**
     * started_at 字段转换
     * 该字段是 timestamp 类型（datetime），需要保持 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    protected function startedAt(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? (is_numeric($value) ? date('Y-m-d H:i:s', (int) $value) : $value) : null,
            set: function ($value) {
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

    /**
     * expires_at 字段转换
     * 该字段是 timestamp 类型（datetime），需要保持 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    protected function expiresAt(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? (is_numeric($value) ? date('Y-m-d H:i:s', (int) $value) : $value) : null,
            set: function ($value) {
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

    /**
     * next_billing_at 字段转换
     * 该字段是 timestamp 类型（datetime），需要保持 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    protected function nextBillingAt(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? (is_numeric($value) ? date('Y-m-d H:i:s', (int) $value) : $value) : null,
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

    /**
     * 关联基础订单.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'id', 'id');
    }

    /**
     * 关联订阅.
     */
    public function subscription()
    {
        return $this->belongsTo(\Modules\VideoSubscription\Models\Subscription::class, 'subscription_id');
    }

    /**
     * 关联套餐.
     */
    public function plan()
    {
        return $this->belongsTo(\Modules\VideoSubscription\Models\VipPlan::class, 'plan_id');
    }
}
