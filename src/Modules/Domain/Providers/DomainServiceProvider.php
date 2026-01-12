<?php

namespace Modules\Domain\Providers;

use XditnModule\Providers\XditnModuleModuleServiceProvider;

class DomainServiceProvider extends XditnModuleModuleServiceProvider
{
    /**
     * route path.
     *
     * @return string
     */
    public function moduleName(): string
    {
        // TODO: Implement path() method.
        return 'domain';
    }
}
