<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use XditnModule\Base\XditnModuleModel;

/**
 * @property $id
 * @property $activity_id
 * @property $coins
 * @property $bonus_coins
 * @property $exchange_rate
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class RechargeOrder extends XditnModuleModel
{
    use HasUuids;

    protected $table = 'pay_recharge_orders';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'activity_id',
        'coins',
        'bonus_coins',
        'exchange_rate',
    ];

    protected array $fields = [
        'id',
        'activity_id',
        'coins',
        'bonus_coins',
        'exchange_rate',
        'created_at',
        'updated_at',
    ];

    protected array $form = [
        'id',
        'activity_id',
        'coins',
        'bonus_coins',
        'exchange_rate',
    ];

    /**
     * 关联基础订单.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id', 'id');
    }

    /**
     * 关联充值活动.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(RechargeActivity::class, 'activity_id');
    }
}
