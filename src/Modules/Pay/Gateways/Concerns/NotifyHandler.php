<?php

declare(strict_types=1);

namespace Modules\Pay\Gateways\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Pay\Contracts\NotifyDataInterface;

/**
 * 回调处理基类.
 */
abstract class NotifyHandler
{
    public function __construct(
        protected NotifyDataInterface $data
    ) {
    }

    /**
     * 处理回调.
     *
     * @throws \Throwable
     */
    public function notify(): mixed
    {
        $outTradeNo = $this->data->getOutTradeNo();
        $lock = Cache::lock('pay-notify-'.$outTradeNo, 10);

        Log::channel('payment')->info('NotifyHandler::notify() 开始处理', [
            'notify_class' => get_class($this),
            'out_trade_no' => $outTradeNo,
            'is_refund' => $this->data->isRefund(),
        ]);

        try {
            if ($this->data->isRefund()) {
                Log::channel('payment')->info('NotifyHandler::notify() 调用 refundNotify()');

                return $this->refundNotify();
            }

            Log::channel('payment')->info('NotifyHandler::notify() 调用 payNotify()');

            return $this->payNotify();
        } finally {
            $lock->release();
        }
    }

    /**
     * 退款回调.
     */
    abstract public function refundNotify(): mixed;

    /**
     * 支付回调.
     */
    abstract public function payNotify(): mixed;
}
