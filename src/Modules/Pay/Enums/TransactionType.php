<?php

declare(strict_types=1);

namespace Modules\Pay\Enums;

use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

/**
 * 资金明细类型枚举.
 */
enum TransactionType: string implements Enum
{
    use EnumTrait;

    // 法币交易
    case RECHARGE = 'recharge'; // 充值
    case WITHDRAW = 'withdraw'; // 提现
    case REFUND = 'refund'; // 退款
    case PAYMENT = 'payment'; // 支付

    // 金币交易
    case COIN_RECHARGE = 'coin_recharge'; // 金币充值（从法币充值获得）
    case COIN_CONSUME = 'coin_consume'; // 金币消费
    case COIN_REFUND = 'coin_refund'; // 金币退款
    case COIN_BONUS = 'coin_bonus'; // 金币赠送

    /**
     * Get the transaction type label (human-readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::RECHARGE => '充值',
            self::WITHDRAW => '提现',
            self::REFUND => '退款',
            self::PAYMENT => '支付',
            self::COIN_RECHARGE => '金币充值',
            self::COIN_CONSUME => '金币消费',
            self::COIN_REFUND => '金币退款',
            self::COIN_BONUS => '金币赠送',
        };
    }

    /**
     * 是否为法币交易.
     */
    public function isFiat(): bool
    {
        return in_array($this, [
            self::RECHARGE,
            self::WITHDRAW,
            self::REFUND,
            self::PAYMENT,
        ], true);
    }

    /**
     * 是否为金币交易.
     */
    public function isCoin(): bool
    {
        return in_array($this, [
            self::COIN_RECHARGE,
            self::COIN_CONSUME,
            self::COIN_REFUND,
            self::COIN_BONUS,
        ], true);
    }

    /**
     * 是否为收入类型.
     */
    public function isIncome(): bool
    {
        return in_array($this, [
            self::RECHARGE,
            self::COIN_RECHARGE,
            self::COIN_BONUS,
            self::REFUND,
            self::COIN_REFUND,
        ], true);
    }

    /**
     * 是否为支出类型.
     */
    public function isExpense(): bool
    {
        return in_array($this, [
            self::WITHDRAW,
            self::PAYMENT,
            self::COIN_CONSUME,
        ], true);
    }
}
