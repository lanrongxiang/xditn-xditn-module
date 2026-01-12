<?php

declare(strict_types=1);

namespace Modules\Pay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 支付订单创建事件.
 */
class PaymentCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public array $request,
        public array $response,
        public string $platform
    ) {
    }
}
