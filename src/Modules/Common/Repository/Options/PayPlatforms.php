<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\Pay\Enums\PayPlatform;

/**
 * 支付平台筛选选项.
 */
class PayPlatforms implements OptionInterface
{
    public function get(): array
    {
        $platforms = [];

        foreach (PayPlatform::cases() as $platform) {
            $platforms[] = [
                'label' => $platform->name(),
                'value' => $platform->value,
            ];
        }

        return $platforms;
    }
}
