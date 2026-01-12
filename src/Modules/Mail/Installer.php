<?php

namespace Modules\Mail;

use Modules\Mail\Providers\MailServiceProvider;
use XditnModule\Support\Module\Installer as ModuleInstaller;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        // TODO: Implement info() method.
        return [
            'title' => '邮件营销',
            'name' => 'mail',
            'path' => 'Mail',
            'keywords' => '',
            'description' => '',
            'provider' => MailServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
        // TODO: Implement requirePackages() method.
    }

    protected function removePackages(): void
    {
        // TODO: Implement removePackages() method.
    }
}
