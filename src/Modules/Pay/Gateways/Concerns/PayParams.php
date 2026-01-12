<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\Concerns;

/**
 * 支付参数基类.
 */
abstract class PayParams
{
    public function __construct(
        protected array $params
    ) {
    }

    /**
     * 获取参数.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}
