<?php

declare(strict_types=1);

namespace XditnModule\Support\Module;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Manager;
use XditnModule\Support\Module\Driver\DatabaseDriver;
use XditnModule\Support\Module\Driver\FileDriver;

class ModuleManager extends Manager
{
    public function __construct(Container|\Closure $container)
    {
        if ($container instanceof \Closure) {
            $container = $container();
        }

        parent::__construct($container);
    }

    public function getDefaultDriver(): ?string
    {
        return $this->config->get('xditn.module.driver.default', $this->defaultDriver());
    }

    /**
     * create file driver.
     */
    public function createFileDriver(): FileDriver
    {
        return new FileDriver();
    }

    /**
     * create database driver.
     */
    public function createDatabaseDriver(): DatabaseDriver
    {
        return new DatabaseDriver();
    }

    /**
     * default driver.
     */
    protected function defaultDriver(): string
    {
        return 'file';
    }
}
