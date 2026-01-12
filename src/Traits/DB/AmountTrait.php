<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

/**
 * Amount Trait.
 *
 * 统一金额处理 Trait
 * - 入库：以分为单位（分）
 * - 出库/展示：系统单位（如 USD）
 * - API 传参：系统单位
 * - 自动转换：入库时转换为分，展示时转换为系统单位
 *
 * 使用方法：
 * 1. 在模型中 use AmountTrait
 * 2. 在模型中定义 protected array $amountFields = ['amount', 'price'] 指定金额字段
 * 3. Trait 会自动为这些字段创建 Attribute 访问器
 *
 * 示例：
 * class RechargeOrder extends XditnModuleModel
 * {
 *     use AmountTrait;
 *
 *     // 指定金额字段，这些字段入库为分，出库为系统单位
 *     protected array $amountFields = ['amount', 'discount_amount'];
 * }
 */
trait AmountTrait
{
    /**
     * 金额字段列表
     * 这些字段入库为分，出库为系统单位.
     *
     * @var array<int, string>
     */
    protected array $amountFields = [];

    /**
     * 金额转换比例（系统单位转分）
     * 例如：USD 转分 = 100，CNY 转分 = 100
     * 如果为 0，则使用默认值 100.
     *
     * @var int
     */
    protected int $amountConversionRate = 0;

    /**
     * 初始化金额字段
     * 不需要额外操作，getAttribute 和 setAttribute 会自动处理.
     */
    protected function initializeAmountTrait(): void
    {
        // 金额字段的转换在 getAttribute 和 setAttribute 中自动处理
        // 这里可以添加其他初始化逻辑（如果需要）
    }

    /**
     * 获取金额转换比例.
     */
    protected function getAmountConversionRate(): int
    {
        if ($this->amountConversionRate > 0) {
            return $this->amountConversionRate;
        }

        return config('xditn.amount.conversion_rate', 100);
    }

    /**
     * 重写 getAttribute 方法，自动处理金额字段.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // 如果是金额字段，且值不为 null，则转换为系统单位
        if (in_array($key, $this->amountFields, true) && $value !== null) {
            $rate = $this->getAmountConversionRate();

            return (float) ($value / $rate);
        }

        return $value;
    }

    /**
     * 重写 setAttribute 方法，自动处理金额字段.
     */
    public function setAttribute($key, $value)
    {
        // 如果是金额字段，且值不为 null，则转换为分
        if (in_array($key, $this->amountFields, true) && $value !== null) {
            $rate = $this->getAmountConversionRate();
            $value = (int) round((float) $value * $rate);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * 将金额从分转换为系统单位.
     *
     * @param int|null $amountInCents 金额（分）
     *
     * @return float|null
     */
    public function centsToAmount(?int $amountInCents): ?float
    {
        if ($amountInCents === null) {
            return null;
        }

        $rate = $this->getAmountConversionRate();

        return (float) ($amountInCents / $rate);
    }

    /**
     * 将金额从系统单位转换为分.
     *
     * @param float|null $amount 金额（系统单位）
     *
     * @return int|null
     */
    public function amountToCents(?float $amount): ?int
    {
        if ($amount === null) {
            return null;
        }

        $rate = $this->getAmountConversionRate();

        return (int) round($amount * $rate);
    }
}
