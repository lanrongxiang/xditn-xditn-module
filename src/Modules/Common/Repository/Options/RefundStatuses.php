<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\Pay\Enums\RefundStatus;

/**
 * 退款状态筛选选项.
 */
class RefundStatuses implements OptionInterface
{
    public function get(): array
    {
        $statuses = [];

        foreach (RefundStatus::cases() as $status) {
            $statuses[] = [
                'label' => $status->name(),
                'value' => $status->value,
            ];
        }

        return $statuses;
    }
}
