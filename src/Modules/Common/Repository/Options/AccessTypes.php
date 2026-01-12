<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\VideoSubscription\Enums\AccessType;

/**
 * 视频访问类型筛选选项.
 */
class AccessTypes implements OptionInterface
{
    public function get(): array
    {
        $types = [];

        foreach (AccessType::cases() as $type) {
            $types[] = [
                'label' => $type->name(),
                'value' => $type->value,
            ];
        }

        return $types;
    }
}
