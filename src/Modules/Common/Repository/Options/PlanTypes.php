<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\VideoSubscription\Enums\PlanType;

/**
 * 套餐类型筛选选项.
 */
class PlanTypes implements OptionInterface
{
    public function get(): array
    {
        $types = [];

        foreach (PlanType::cases() as $type) {
            $types[] = [
                'label' => $type->name(),
                'value' => $type->value,
            ];
        }

        return $types;
    }
}
