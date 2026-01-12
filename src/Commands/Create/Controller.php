<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class Controller extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:controller {module} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create xditnmodule controller';

    public function handle()
    {
        $controllerPath = XditnModule::getModuleControllerPath($this->argument('module'));

        $file = $controllerPath.$this->getControllerFile();

        if (File::exists($file)) {
            $answer = $this->ask($file.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                exit;
            }
        }

        File::put($file, Str::of($this->getStubContent())->replace([
            '{namespace}', '{controller}',
        ], [trim(XditnModule::getModuleControllerNamespace($this->argument('module')), '\\'), $this->getControllerName()])->toString());

        if (File::exists($file)) {
            $this->info($file.' has been created');
        } else {
            $this->error($file.' create failed');
        }
    }

    protected function getControllerFile(): string
    {
        return $this->getControllerName().'.php';
    }

    protected function getControllerName(): string
    {
        return Str::of($this->argument('name'))
            ->whenContains('Controller', function ($str) {
                return $str;
            }, function ($str) {
                return $str->append('Controller');
            })->ucfirst()->toString();
    }

    /**
     * get stub content.
     */
    protected function getStubContent(): string
    {
        return File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'controller.stub');
    }
}
