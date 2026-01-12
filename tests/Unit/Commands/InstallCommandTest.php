<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use XditnModule\Commands\InstallCommand;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 设置测试环境配置
        Config::set('xditn.module.default', ['develop', 'user', 'common']);
    }

    public function test_get_modules_to_install_with_specified_modules(): void
    {
        $command = new InstallCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getModulesToInstall');
        $method->setAccessible(true);

        // 创建模拟的输入对象
        $input = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
        $input->method('getOption')
            ->with('modules')
            ->willReturn(['Ai', 'Cms']);

        $command->setLaravel($this->app);
        $command->setInput($input);

        $modules = $method->invoke($command);

        // 应该包含指定的模块和默认模块
        $this->assertContains('Ai', $modules);
        $this->assertContains('Cms', $modules);
        $this->assertContains('Develop', $modules);
        $this->assertContains('User', $modules);
        $this->assertContains('Common', $modules);
    }

    public function test_get_modules_to_install_without_specified_modules(): void
    {
        // 清除配置文件中的默认模块，测试默认行为
        Config::set('xditn.module.default', []);

        $command = new InstallCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getModulesToInstall');
        $method->setAccessible(true);

        // 创建模拟的输入对象（没有指定模块）
        $input = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
        $input->method('getOption')
            ->with('modules')
            ->willReturn([]);

        $command->setLaravel($this->app);
        $command->setInput($input);

        $modules = $method->invoke($command);

        // 应该包含默认的核心模块
        $this->assertContains('permissions', $modules);
        $this->assertContains('system', $modules);
    }

    public function test_get_modules_to_install_with_config_default_modules(): void
    {
        // 设置配置文件中的默认模块
        Config::set('xditn.module.default', ['Ai', 'Cms']);

        $command = new InstallCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getModulesToInstall');
        $method->setAccessible(true);

        // 创建模拟的输入对象（没有指定模块）
        $input = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
        $input->method('getOption')
            ->with('modules')
            ->willReturn([]);

        $command->setLaravel($this->app);
        $command->setInput($input);

        $modules = $method->invoke($command);

        // 应该包含配置文件中的默认模块
        $this->assertContains('Ai', $modules);
        $this->assertContains('Cms', $modules);
    }

    public function test_get_modules_to_install_removes_duplicates(): void
    {
        // 设置配置文件中的默认模块
        Config::set('xditn.module.default', ['Ai', 'Cms']);

        $command = new InstallCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getModulesToInstall');
        $method->setAccessible(true);

        // 创建模拟的输入对象（命令行参数和配置文件中都有相同的模块）
        $input = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
        $input->method('getOption')
            ->with('modules')
            ->willReturn(['Ai']);

        $command->setLaravel($this->app);
        $command->setInput($input);

        $modules = $method->invoke($command);

        // 应该没有重复的模块
        $this->assertEquals(1, count(array_filter($modules, fn ($m) => $m === 'Ai')));
    }

    public function test_module_names_are_normalized(): void
    {
        $command = new InstallCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getModulesToInstall');
        $method->setAccessible(true);

        // 创建模拟的输入对象（不同大小写的模块名）
        $input = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
        $input->method('getOption')
            ->with('modules')
            ->willReturn(['ai', 'CMS', 'WeChat']);

        $command->setLaravel($this->app);
        $command->setInput($input);

        $modules = $method->invoke($command);

        // 模块名应该被标准化为首字母大写
        $this->assertContains('Ai', $modules);
        $this->assertContains('Cms', $modules);
        $this->assertContains('Wechat', $modules);
    }
}
