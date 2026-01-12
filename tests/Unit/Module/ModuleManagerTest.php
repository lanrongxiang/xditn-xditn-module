<?php

declare(strict_types=1);

namespace Tests\Unit\Module;

use Tests\TestCase;
use XditnModule\Support\Module\Driver\DatabaseDriver;
use XditnModule\Support\Module\Driver\FileDriver;
use XditnModule\Support\Module\ModuleManager;

class ModuleManagerTest extends TestCase
{
    protected ModuleManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ModuleManager($this->app);
    }

    public function test_get_default_driver_returns_file_by_default(): void
    {
        $driver = $this->manager->getDefaultDriver();

        $this->assertEquals('file', $driver);
    }

    public function test_create_file_driver(): void
    {
        $driver = $this->manager->createFileDriver();

        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function test_create_database_driver(): void
    {
        $driver = $this->manager->createDatabaseDriver();

        $this->assertInstanceOf(DatabaseDriver::class, $driver);
    }

    public function test_driver_returns_file_driver_by_default(): void
    {
        $driver = $this->manager->driver();

        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function test_driver_returns_specified_driver(): void
    {
        $fileDriver = $this->manager->driver('file');
        $this->assertInstanceOf(FileDriver::class, $fileDriver);
    }

    public function test_get_default_driver_respects_config(): void
    {
        // 设置配置为数据库驱动
        $this->app['config']->set('xditn.module.driver.default', 'database');

        $manager = new ModuleManager($this->app);
        $driver = $manager->getDefaultDriver();

        $this->assertEquals('database', $driver);
    }
}
