<?php

namespace Modules\User\Providers;

use Modules\User\Console\PasswordCommand;
use Modules\User\Events\Login;
use Modules\User\Listeners\Login as LoginListener;
use Modules\User\Middlewares\OperatingMiddleware;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class UserServiceProvider extends XditnModuleModuleServiceProvider
{
    protected array $events = [
        Login::class => LoginListener::class,
    ];

    protected array $commands = [
        PasswordCommand::class,
    ];

    /**
     * 模块名称.
     */
    public function moduleName(): string|array
    {
        return 'user';
    }

    /**
     * @return string[]
     */
    protected function middlewares(): array
    {
        return [OperatingMiddleware::class];
    }
}
