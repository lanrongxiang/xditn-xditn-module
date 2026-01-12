<?php

namespace Modules\Common\Providers;

use Modules\Common\Console\Area;
use Modules\Common\Console\CleanupChunks;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class CommonServiceProvider extends XditnModuleModuleServiceProvider
{
    protected array $commands = [
        Area::class,
        CleanupChunks::class,
    ];

    /**
     * route path.
     */
    public function moduleName(): string|array
    {
        // TODO: Implement path() method.
        return 'common';
    }
}
