<?php

namespace Modules\Openapi\Providers;

use Modules\Openapi\Support\OpenapiAuth;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class OpenapiServiceProvider extends XditnModuleModuleServiceProvider
{
    /**
     * route path.
     */
    public function moduleName(): string
    {
        // TODO: Implement path() method.
        return 'openapi';
    }

    public function boot(): void
    {
        $this->app->singleton(OpenapiAuth::class, fn () => new OpenapiAuth());
    }
}
