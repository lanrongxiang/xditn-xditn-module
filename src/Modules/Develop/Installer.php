<?php

declare(strict_types=1);

namespace Modules\Develop;

use Modules\Develop\Providers\DevelopServiceProvider;
use XditnModule\Support\Module\Installer as ModuleInstaller;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '开发工具',
            'name' => 'develop',
            'path' => 'develop',
            'keywords' => '开发工具，代码生成',
            'description' => '开发工具模块，提供代码生成等功能',
            'provider' => DevelopServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
