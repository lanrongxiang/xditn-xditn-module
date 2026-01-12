<?php

namespace Modules\Develop\Listeners;

use XditnModule\Events\Module\Deleted;
use XditnModule\XditnModule;

class DeletedListener
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

    public function handle(Deleted $event): void
    {
        XditnModule::deleteModulePath($event->module['path']);
    }
}
