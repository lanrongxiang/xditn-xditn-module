<?php

namespace Modules\Ai\Providers;

use XditnModule\Providers\XditnModuleModuleServiceProvider;

class AiServiceProvider extends XditnModuleModuleServiceProvider
{
    /**
     * route path.
     *
     * @return string
     */
    public function moduleName(): string
    {
        // TODO: Implement path() method.
        return 'ai';
    }
}
