<?php

namespace XditnModule\Octane;

use Illuminate\Contracts\Debug\ExceptionHandler;
use XditnModule\Exceptions\Handler;

class RegisterExceptionHandler
{
    /**
     * Handle the event.
     *
     * @param mixed $event
     */
    public function handle($event): void
    {
        if (isRequestFromDashboard()) {
            $event->sandbox->singleton(ExceptionHandler::class, Handler::class);
        }
    }
}
