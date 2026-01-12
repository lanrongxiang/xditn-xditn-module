<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\Concerns;

use Modules\Pay\Contracts\NotifyDataInterface;

/**
 * 回调数据基类.
 */
abstract class NotifyData implements NotifyDataInterface
{
    public function __construct(
        protected array $data
    ) {
    }

    /**
     * 获取数据.
     */
    public function get(string $key = ''): mixed
    {
        if (empty($key)) {
            return $this->data;
        }

        return data_get($this->data, $key);
    }

    /**
     * 获取原始数据.
     */
    public function getRaw(): array
    {
        return $this->data;
    }

    /**
     * 转换为 JSON.
     */
    public function json(bool $isPretty = true): string
    {
        $flags = JSON_UNESCAPED_UNICODE;
        if ($isPretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($this->data, $flags) ?: '{}';
    }
}
