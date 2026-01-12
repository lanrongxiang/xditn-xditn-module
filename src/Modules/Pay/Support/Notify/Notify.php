<?php

namespace Modules\Pay\Support\Notify;

use Illuminate\Support\Facades\Cache;
use Modules\Pay\Support\NotifyData\NotifyDataInterface;

/**
 * Class Notify.
 *
 * 回调类
 */
abstract class Notify
{
    public function __construct(
        protected NotifyDataInterface $data
    ) {
    }

    /**
     * 回调.
     *
     * @return mixed|void
     *
     * @throws \Throwable
     */
    public function notify()
    {
        $outTradeNo = $this->data->getOutTradeNo();
        $lock = Cache::lock('pay-notify-'.$outTradeNo, 10);

        \Illuminate\Support\Facades\Log::channel('payment')->info('Notify::notify() 开始处理', [
            'notify_class' => get_class($this),
            'out_trade_no' => $outTradeNo,
            'is_refund' => $this->data->isRefund(),
        ]);

        try {
            if ($this->data->isRefund()) {
                \Illuminate\Support\Facades\Log::channel('payment')->info('Notify::notify() 调用 refundNotify()', [
                    'notify_class' => get_class($this),
                ]);

                return $this->refundNotify();
            }

            // 修复：应该是 !isRefund() 才调用 payNotify()
            \Illuminate\Support\Facades\Log::channel('payment')->info('Notify::notify() 调用 payNotify()', [
                'notify_class' => get_class($this),
            ]);

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
