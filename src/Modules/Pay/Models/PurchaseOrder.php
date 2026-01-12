<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use XditnModule\Base\XditnModuleModel;

/**
 * @property $id
 * @property $episode_id
 * @property $video_id
 * @property $coins
 * @property $purchase_type
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class PurchaseOrder extends XditnModuleModel
{
    use HasUuids;

    protected $table = 'pay_purchase_orders';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'episode_id',
        'video_id',
        'coins',
        'purchase_type',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $fields = [
        'id',
        'episode_id',
        'video_id',
        'coins',
        'purchase_type',
        'created_at',
        'updated_at',
    ];

    protected array $form = [
        'id',
        'episode_id',
        'video_id',
        'coins',
        'purchase_type',
    ];

    /**
     * 关联基础订单.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'id', 'id');
    }
}
