<?php

namespace Modules\Ai;

use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Modules\Ai\Providers\AiServiceProvider;
use XditnModule\Support\Module\Installer as ModuleInstaller;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        // TODO: Implement info() method.
        return [
            'title' => 'AI助手',
            'name' => 'ai',
            'path' => 'Ai',
            'keywords' => 'ai,ai模型,ai对接',
            'description' => 'AI助手模块',
            'provider' => AiServiceProvider::class,
        ];
    }

    /**
     * @throws PhpVersionNotSupportedException
     */
    protected function requirePackages(): void
    {
        // TODO: Implement requirePackages() method.
        $this->composer()->require('league/commonmark');
    }

    protected function removePackages(): void
    {
        // TODO: Implement removePackages() method.
        $this->composer()->remove('league/commonmark');
    }
}
