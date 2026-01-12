<?php

namespace Modules\Develop\Listeners;

use Modules\Develop\Support\Generate\Module;
use XditnModule\Events\Module\Created;

class CreatedListener
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

    public function handle(Created $event): void
    {
        $module = $event->module;

        (new Module(
            $module['path'],
            $module['dirs']['controllers'],
            $module['dirs']['models'],
            $module['dirs']['requests'],
            $module['dirs']['database'],
            $module['title'],
            $module['keywords'] ?? '',
            $module['description'] ?? '',
        )
        )->create();
    }
}
