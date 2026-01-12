<?php

declare(strict_types=1);

namespace Modules\Pay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 支付完成事件.
 */
class PaymentCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $orderNo,
        public string $outTradeNo,
        public string $platform,
        public array $data = []
    ) {
    }
}
