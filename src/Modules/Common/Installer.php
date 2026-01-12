<?php

declare(strict_types=1);

namespace Modules\Common;

use Modules\Common\Providers\CommonServiceProvider;
use XditnModule\Support\Module\Installer as ModuleInstaller;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '公共模块',
            'name' => 'common',
            'path' => 'common',
            'keywords' => '公共模块，通用功能',
            'description' => '公共模块，提供上传、选项、设置等通用功能',
            'provider' => CommonServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
