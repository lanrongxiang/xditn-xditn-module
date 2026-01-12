<?php

namespace Modules\Develop\Providers;

use Modules\Develop\Console\ModuleInitCommand;
use Modules\Develop\Listeners\CreatedListener;
use Modules\Develop\Listeners\DeletedListener;
use XditnModule\Events\Module\Created;
use XditnModule\Events\Module\Deleted;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class DevelopServiceProvider extends XditnModuleModuleServiceProvider
{
    protected array $events = [
        Created::class => CreatedListener::class,

        Deleted::class => DeletedListener::class,
    ];

    /**
     * route path.
     */
    public function moduleName(): string|array
    {
        // TODO: Implement path() method.
        return 'develop';
    }

    public function boot()
    {
        $this->commands([
            ModuleInitCommand::class,
        ]);
    }
}
