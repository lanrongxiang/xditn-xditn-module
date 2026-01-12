<?php

declare(strict_types=1);

namespace Modules\Pay\Providers;

use Modules\Pay\Events\PayEvent;
use Modules\Pay\Events\PaymentCompleted;
use Modules\Pay\Events\PaymentCreated;
use Modules\Pay\Events\PaymentFailed;
use Modules\Pay\Events\PayNotifyEvent;
use Modules\Pay\Listeners\PayListener;
use Modules\Pay\Listeners\PaymentCompletedLogListener;
use Modules\Pay\Listeners\PaymentCreatedLogListener;
use Modules\Pay\Listeners\PaymentFailedLogListener;
use Modules\Pay\Listeners\PaymentOrderStatusListener;
use Modules\Pay\Listeners\PaymentStatisticsListener;
use Modules\Pay\Listeners\PayNotifyListener;
use Modules\Pay\Listeners\PayNotifyLogListener;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class PayServiceProvider extends XditnModuleModuleServiceProvider
{
    protected array $events = [
        PayNotifyEvent::class => [
            PayNotifyLogListener::class,
            PayNotifyListener::class,
        ],
        PayEvent::class => PayListener::class,
        PaymentCreated::class => [
            PaymentCreatedLogListener::class,
            PaymentOrderStatusListener::class,
            PaymentStatisticsListener::class,
        ],
        PaymentCompleted::class => PaymentCompletedLogListener::class,
        PaymentFailed::class => PaymentFailedLogListener::class,
    ];

    /**
     * 注册命令.
     */
    protected array $commands = [];

    /**
     * route path.
     */
    public function moduleName(): string
    {
        return 'Pay';
    }

    /**
     * 注册定时任务（已由定时任务看板管理，无需在此注册）.
     */
    public function boot(): void
    {
        // 定时任务已由系统定时任务看板统一管理
    }
}
