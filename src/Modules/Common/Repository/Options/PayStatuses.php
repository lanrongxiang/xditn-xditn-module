<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\Pay\Enums\PayStatus;

/**
 * 支付状态筛选选项.
 */
class PayStatuses implements OptionInterface
{
    public function get(): array
    {
        $statuses = [];

        foreach (PayStatus::cases() as $status) {
            $statuses[] = [
                'label' => $status->name(),
                'value' => $status->value,
            ];
        }

        return $statuses;
    }
}
