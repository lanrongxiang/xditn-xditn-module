<?php

declare(strict_types=1);

namespace Tests\Unit\Module;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use XditnModule\Support\Module\Driver\FileDriver;

class FileDriverTest extends TestCase
{
    protected FileDriver $driver;

    protected string $moduleJsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = new FileDriver();
        $this->moduleJsonPath = storage_path('app').DIRECTORY_SEPARATOR.'modules.json';
    }

    protected function tearDown(): void
    {
        // 清理测试文件
        if (File::exists($this->moduleJsonPath)) {
            File::delete($this->moduleJsonPath);
        }

        parent::tearDown();
    }

    public function test_all_returns_empty_collection_when_file_not_exists(): void
    {
        // 确保文件不存在
        if (File::exists($this->moduleJsonPath)) {
            File::delete($this->moduleJsonPath);
        }

        $result = $this->driver->all();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_all_returns_empty_collection_when_file_is_empty(): void
    {
        File::put($this->moduleJsonPath, '');

        $result = $this->driver->all();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_create_module(): void
    {
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '测试描述',
            'keywords' => 'test,module',
        ];

        $result = $this->driver->create($module);

        $this->assertTrue($result);

        // 验证文件内容
        $modules = $this->driver->all();
        $this->assertCount(1, $modules);
        $this->assertEquals('测试模块', $modules->first()['title']);
    }

    public function test_show_module(): void
    {
        // 先创建一个模块
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '测试描述',
            'keywords' => 'test,module',
        ];
        $this->driver->create($module);

        $result = $this->driver->show('test');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals('测试模块', $result['title']);
    }

    public function test_update_module(): void
    {
        // 先创建一个模块
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '测试描述',
            'keywords' => 'test,module',
        ];
        $this->driver->create($module);

        // 更新模块
        $updatedModule = [
            'title' => '更新后的模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '更新后的描述',
            'keywords' => 'updated',
            'enable' => true,
        ];
        $result = $this->driver->update('test', $updatedModule);

        $this->assertTrue($result);

        // 验证更新
        $modules = $this->driver->all();
        $this->assertEquals('更新后的模块', $modules->first()['title']);
    }

    public function test_delete_module(): void
    {
        // 先创建一个模块
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '测试描述',
            'keywords' => 'test,module',
        ];
        $this->driver->create($module);

        $result = $this->driver->delete('test');

        $this->assertTrue($result);

        // 验证删除
        $modules = $this->driver->all();
        $this->assertTrue($modules->isEmpty());
    }

    public function test_dis_or_enable(): void
    {
        // 先创建一个模块
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '测试描述',
            'keywords' => 'test,module',
        ];
        $this->driver->create($module);

        // 禁用模块（默认是启用的）
        $this->driver->disOrEnable('test');

        $modules = $this->driver->all();
        $this->assertFalse($modules->first()['enable']);

        // 再次切换（启用）
        $this->driver->disOrEnable('test');

        $modules = $this->driver->all();
        $this->assertTrue($modules->first()['enable']);
    }

    public function test_get_enabled_returns_only_enabled_modules(): void
    {
        // 创建两个模块
        $module1 = [
            'title' => '模块1',
            'name' => 'module1',
            'path' => 'module1',
            'description' => '',
            'keywords' => '',
        ];
        $module2 = [
            'title' => '模块2',
            'name' => 'module2',
            'path' => 'module2',
            'description' => '',
            'keywords' => '',
        ];

        $this->driver->create($module1);
        $this->driver->create($module2);

        // 禁用第一个模块
        $this->driver->disOrEnable('module1');

        $enabled = $this->driver->getEnabled();

        $this->assertCount(1, $enabled);
        $this->assertEquals('模块2', $enabled->first()['title']);
    }

    public function test_enabled_returns_true_for_enabled_module(): void
    {
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '',
            'keywords' => '',
        ];
        $this->driver->create($module);

        $this->assertTrue($this->driver->enabled('test'));
    }

    public function test_enabled_returns_false_for_disabled_module(): void
    {
        $module = [
            'title' => '测试模块',
            'name' => 'test',
            'path' => 'test',
            'description' => '',
            'keywords' => '',
        ];
        $this->driver->create($module);

        // 禁用模块
        $this->driver->disOrEnable('test');

        $this->assertFalse($this->driver->enabled('test'));
    }

    public function test_enabled_returns_false_for_non_existent_module(): void
    {
        $this->assertFalse($this->driver->enabled('non_existent'));
    }

    public function test_all_with_search(): void
    {
        // 创建多个模块
        $module1 = [
            'title' => '用户管理',
            'name' => 'user',
            'path' => 'user',
            'description' => '',
            'keywords' => '',
        ];
        $module2 = [
            'title' => '支付模块',
            'name' => 'pay',
            'path' => 'pay',
            'description' => '',
            'keywords' => '',
        ];

        $this->driver->create($module1);
        $this->driver->create($module2);

        // 搜索包含"用户"的模块
        $result = $this->driver->all(['title' => '用户']);

        $this->assertCount(1, $result);
        $this->assertEquals('用户管理', $result->first()['title']);
    }
}
