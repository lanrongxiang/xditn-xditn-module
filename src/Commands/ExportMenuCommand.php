<?php

namespace XditnModule\Commands;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XditnModule\Base\XditnModuleModel;
use XditnModule\XditnModule;

class ExportMenuCommand extends XditnModuleCommand
{
    protected $signature = 'xditn:module:export:menu {--p : 是否使用树形结构} {--module= : 指定导出的模块}';

    protected $description = 'export xditnmodule menu data';

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        if ($moduleOfOption = $this->option('module')) {
            $module = [$moduleOfOption];
        } else {
            $modules = XditnModule::getAllModules();

            try {
                $selectedModulesTitle = $this->choice(
                    '选择导出菜单的模块',
                    $modules->pluck('title')->toArray(),
                    attempts: 1,
                    multiple: true
                );
            } catch (\Exception $e) {
                $this->error('未选择任何模块');
                exit;
            }

            $module = [];
            $modules->each(function ($item) use ($selectedModulesTitle, &$module) {
                if (in_array($item['title'], $selectedModulesTitle)) {
                    $module[] = $item['name'];
                }
            });
        }

        $model = $this->createModel();

        $data = $model->whereIn('module', $module)->get()->toTree();
        $data = 'return '.var_export(json_decode($data, true), true).';';
        $this->exportSeed($data, $module);

        $this->info('模块菜单导出成功');
    }

    protected function exportSeed($data, $module): void
    {
        $module = $module[0];

        $stub = File::get(__DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'menuSeeder.stub');

        $class = ucfirst($module).'MenusSeeder';

        $stub = str_replace('{CLASS}', $class, $stub);

        File::put(XditnModule::getModuleSeederPath($module).$class.'.php', str_replace('{menus}', $data, $stub));
    }

    protected function createModel(): XditnModuleModel
    {
        return new class() extends XditnModuleModel {
            protected $table = 'permissions';
        };
    }
}
