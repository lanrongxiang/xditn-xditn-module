<?php

namespace Modules\Member\Providers;

use XditnModule\Providers\XditnModuleModuleServiceProvider;

class MemberServiceProvider extends XditnModuleModuleServiceProvider
{
    /**
     * route path.
     */
    public function moduleName(): string
    {
        // TODO: Implement path() method.
        return 'Member';
    }
}
