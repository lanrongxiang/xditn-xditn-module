<?php

namespace Modules\Permissions\Providers;

use Modules\Permissions\Middlewares\PermissionGate;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class PermissionsServiceProvider extends XditnModuleModuleServiceProvider
{
    /**
     * middlewares.
     *
     * @return string[]
     */
    protected function middlewares(): array
    {
        return [PermissionGate::class];
    }

    /**
     * route path.
     */
    public function moduleName(): string|array
    {
        // TODO: Implement path() method.
        return 'permissions';
    }
}
