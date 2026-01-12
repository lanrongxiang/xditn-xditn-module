<?php

declare(strict_types=1);

namespace Modules\Pay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 支付失败事件.
 */
class PaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public array $request,
        public string $error,
        public string $platform,
        public ?string $trace = null
    ) {
    }
}
