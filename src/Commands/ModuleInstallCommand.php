<?php

namespace XditnModule\Commands;

use Illuminate\Support\Str;
use XditnModule\Facade\Module;
use XditnModule\XditnModule;

class ModuleInstallCommand extends XditnModuleCommand
{
    protected $signature = 'xditn:module:install {--f : 强制安装} {--all : 安装所有模块}';

    protected $description = 'install xditnmodule module';

    public function handle(): void
    {
        $installers = [];

        foreach (XditnModule::getModulesPath() as $modulePath) {
            if (is_dir($modulePath)) {
                try {
                    $installers[] = XditnModule::getModuleInstaller(Str::of($modulePath)->explode(DIRECTORY_SEPARATOR)->last());
                } catch (\Throwable $e) {
                }
            }
        }

        // 安装所有模块
        if ($this->option('all')) {
            $modules = Module::all();
            foreach ($installers as $installer) {
                if (!$modules->pluck('name')->contains($installer->getInfo()['name'])) {
                    $installer->install();
                    $this->comment('✔ '.$installer->getInfo()['title'].'安装成功');
                }
            }
        } else {
            $modules = [];
            foreach ($installers as $installer) {
                $modules[] = $installer->getInfo();
            }

            try {
                $selectedModulesTitle = $this->choice(
                    '选择你要按照的模块',
                    array_column($modules, 'title'),
                    attempts: 1,
                    multiple: true
                );
            } catch (\Throwable $e) {
                $this->error('未选择任何模块');
                exit;
            }

            try {

                $selectInstallers = [];
                foreach ($selectedModulesTitle as $title) {
                    foreach ($modules as $module) {
                        if ($module['title'] == $title) {
                            $selectInstallers[] = XditnModule::getModuleInstaller($module['name']);
                            break;
                        }
                    }
                }

                if ($this->option('f')) {
                    $answer = $this->askFor('强制安装模块，不会删除当前模块的数据库数据。是否继续?', 'y');
                    if (in_array(strtolower($answer), ['y', 'yes'])) {
                        foreach ($selectInstallers as $selectInstaller) {
                            $selectInstaller->uninstall();
                            $selectInstaller->install();
                        }
                        $this->info(implode(',', $selectedModulesTitle).' 已强制安装');
                    }
                } else {
                    foreach ($selectInstallers as $selectInstaller) {
                        $selectInstaller->install();
                    }
                    $this->info(implode(',', $selectedModulesTitle).' 已安装成功');
                }
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());
            }
        }
    }
}
