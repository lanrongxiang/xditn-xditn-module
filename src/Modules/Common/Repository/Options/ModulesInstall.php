<?php

namespace Modules\Common\Repository\Options;

use XditnModule\Support\Module\ModuleRepository;
use XditnModule\XditnModule;

class ModulesInstall implements OptionInterface
{
    public function get(): array
    {
        $modules = [];

        $enabledModuleNames = app(ModuleRepository::class)->getEnabled()->pluck('name')->merge(
            config('xditn.module.default', [])
        );

        foreach (XditnModule::getModulesPath() as $module) {
            try {
                $installer = XditnModule::getModuleInstaller(basename($module));

                $info = $installer->getInfo();

                if (!$enabledModuleNames->contains($info['name'])) {
                    $modules[] = [
                        'label' => $info['title'],
                        'value' => $info['name'],
                    ];
                }
            } catch (\Exception $e) {

            }
        }

        return $modules;
    }
}
