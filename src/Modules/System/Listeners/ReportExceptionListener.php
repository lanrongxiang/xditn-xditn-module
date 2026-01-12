<?php

namespace Modules\System\Listeners;

use Modules\System\Models\Webhooks;
use Modules\System\Support\Webhook;
use XditnModule\Events\ReportException;

class ReportExceptionListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ReportException $event
     *
     * @return void
     */
    public function handle(ReportException $event): void
    {
        //
        $webhook = new Webhook(Webhooks::exceptions());

        $webhook->setValues([$event->exception->getMessage(), $event->exception->getFile()])->send();
    }
}
