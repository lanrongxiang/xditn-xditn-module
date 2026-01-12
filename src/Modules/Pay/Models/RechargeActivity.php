<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use XditnModule\Base\XditnModuleModel;

/**
 * @property $id
 * @property $title
 * @property $description
 * @property $type
 * @property $min_amount
 * @property $max_amount
 * @property $discount_rate
 * @property $bonus_coins
 * @property $original_coins
 * @property $start_at
 * @property $end_at
 * @property $status
 * @property $sort
 */
class RechargeActivity extends XditnModuleModel
{
    protected $table = 'recharge_activities';

    /**
     * 禁用自动将 null 转换为空字符串
     * 对于 decimal 等字段，需要保持 null 值而不是空字符串.
     */
    protected bool $autoNull2EmptyString = false;

    protected $fillable = [
        'id',
        'title',
        'description',
        'type',
        'min_amount',
        'max_amount',
        'discount_rate',
        'bonus_coins',
        'original_coins',
        'start_at',
        'end_at',
        'status',
        'sort',
    ];

    protected array $fields = [
        'id',
        'title',
        'description',
        'type',
        'min_amount',
        'max_amount',
        'discount_rate',
        'bonus_coins',
        'original_coins',
        'start_at',
        'end_at',
        'status',
        'sort',
        'created_at',
    ];

    protected array $form = [
        'title',
        'description',
        'type',
        'min_amount',
        'max_amount',
        'discount_rate',
        'bonus_coins',
        'original_coins',
        'start_at',
        'end_at',
        'status',
        'sort',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public array $searchable = [
        'title' => 'like',
        'type' => '=',
        'status' => '=',
    ];

    /**
     * 活动类型：折扣活动.
     */
    public const TYPE_DISCOUNT = 1;

    /**
     * 活动类型：充值档位送金币
     */
    public const TYPE_TIER_BONUS = 2;

    /**
     * 金额转换（分转元）.
     */
    protected function minAmount(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value / 100,
            set: fn ($value) => (int) ($value * 100)
        );
    }

    /**
     * 金额转换（分转元）.
     */
    protected function maxAmount(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? ($value / 100) : null,
            set: fn ($value) => $value ? (int) ($value * 100) : null
        );
    }

    /**
     * 检查活动是否有效.
     */
    public function isValid(): bool
    {
        if ($this->status != 1) {
            return false;
        }

        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) {
            return false;
        }
        if ($this->end_at && $now->gt($this->end_at)) {
            return false;
        }

        return true;
    }

    /**
     * 检查充值金额是否满足活动条件.
     *
     * @param int $amount 充值金额（分）
     */
    public function matchesAmount(int $amount): bool
    {
        // 使用 getRawOriginal 获取数据库原始值（分），避免访问器转换
        $minAmount = $this->getRawOriginal('min_amount');
        $maxAmount = $this->getRawOriginal('max_amount');

        if ($amount < $minAmount) {
            return false;
        }

        if ($maxAmount !== null && $amount > $maxAmount) {
            return false;
        }

        return true;
    }

    /**
     * 计算充值金额对应的金币数（考虑活动和赠送）.
     *
     * @param int $amount 充值金额（分）
     * @param int $baseCoins 基础金币数（已按汇率计算）
     *
     * @return array ['coins' => int, 'bonus_coins' => int, 'total_coins' => int, 'activity' => RechargeActivity|null]
     */
    public function calculateCoins(int $amount, int $baseCoins): array
    {
        $coins = $baseCoins;
        $bonusCoins = 0;

        if ($this->isValid() && $this->matchesAmount($amount)) {
            if ($this->type == self::TYPE_DISCOUNT) {
                // 折扣活动：应用折扣率
                // 折扣率是百分比，例如99表示99折（即0.99）
                $coins = (int) ($baseCoins * ($this->discount_rate / 100));
            } elseif ($this->type == self::TYPE_TIER_BONUS) {
                // 充值档位送金币：基础金币 + 赠送金币
                $bonusCoins = $this->bonus_coins;
            }
        }

        return [
            'coins' => $coins,
            'bonus_coins' => $bonusCoins,
            'total_coins' => $coins + $bonusCoins,
            'activity' => $this,
        ];
    }
}
