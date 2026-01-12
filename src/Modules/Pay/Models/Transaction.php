<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Member\Models\Members;
use Modules\Pay\Enums\TransactionType;
use XditnModule\Traits\DB\BaseOperate;
use XditnModule\Traits\DB\DateformatTrait;
use XditnModule\Traits\DB\ScopeTrait;
use XditnModule\Traits\DB\Trans;
use XditnModule\Traits\DB\WithAttributes;

/**
 * @property $id
 * @property $user_id
 * @property $transaction_no
 * @property $type
 * @property $currency_type
 * @property $amount
 * @property $currency
 * @property $direction
 * @property $balance_before
 * @property $balance_after
 * @property $order_id
 * @property $order_no
 * @property $related_type
 * @property $related_id
 * @property $description
 * @property $extra_data
 * @property $transaction_at
 * @property $created_at
 * @property $updated_at
 */
class Transaction extends Model
{
    use BaseOperate;
    use DateformatTrait;
    use HasUuids;
    use ScopeTrait;
    use Trans;
    use WithAttributes;

    protected $table = 'pay_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * 时间格式（Unix 时间戳）
     * XditnModule 的 createdAt() 和 updatedAt() 宏创建的是 unsignedInteger 类型字段
     * 需要使用 Unix 时间戳格式（'U'）而不是 datetime 格式.
     *
     * 注意：transaction_at 字段是 timestamp 类型（datetime），需要单独处理
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'id',
        'user_id',
        'transaction_no',
        'type',
        'currency_type',
        'amount',
        'currency',
        'direction',
        'balance_before',
        'balance_after',
        'order_id',
        'order_no',
        'related_type',
        'related_id',
        'description',
        'extra_data',
        'transaction_at',
        'created_at',
        'updated_at',
    ];

    protected array $fields = [
        'id',
        'user_id',
        'transaction_no',
        'type',
        'currency_type',
        'amount',
        'currency',
        'direction',
        'order_id',
        'transaction_at',
        'created_at',
    ];

    protected array $form = [
        'user_id',
        'transaction_no',
        'type',
        'currency_type',
        'amount',
        'currency',
        'direction',
        'order_id',
        'description',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'extra_data' => 'array',
        // transaction_at 是 timestamp 类型（datetime），需要保持 datetime 格式
        // 使用 'datetime:Y-m-d H:i:s' 确保格式正确，不受 $dateFormat 影响
        'transaction_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setSearchable([
            'transaction_no' => 'like',
            'user_id' => '=',
            'type' => '=',
            'order_id' => '=',
        ]);
    }

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
     * transaction_at 字段转换
     * 该字段是 timestamp 类型（datetime），需要保持 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    protected function transactionAt(): Attribute
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
     * 重写 setAttribute 方法，确保 transaction_at 字段使用 datetime 格式
     * 不受 $dateFormat = 'U' 影响.
     */
    public function setAttribute($key, $value)
    {
        // 如果是 transaction_at 字段，且值是 DateTime 对象，直接转换为 datetime 字符串
        if ($key === 'transaction_at' && $value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * 关联用户.
     */
    public function user()
    {
        return $this->belongsTo(Members::class, 'user_id');
    }

    /**
     * 关联订单.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 多态关联.
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }
}
