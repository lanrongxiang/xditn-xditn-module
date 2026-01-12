<?php

declare(strict_types=1);

namespace Modules\Pay\Contracts;

/**
 * 支付接口.
 *
 * 所有支付网关实现都必须实现此接口
 */
interface PayInterface
{
    /**
     * 发起退款.
     */
    public function refund(array $params): mixed;

    /**
     * 处理回调通知.
     */
    public function notify(): mixed;
}
