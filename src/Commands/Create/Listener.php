<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class Listener extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:listener {module} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create xditnmodule listener';

    public function handle()
    {
        $eventPath = XditnModule::getModuleListenersPath($this->argument('module'));

        $file = $eventPath.$this->getListenerFile();

        if (File::exists($file)) {
            $answer = $this->ask($file.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                exit;
            }
        }

        File::put($file, Str::of($this->getStubContent())->replace([
            '{namespace}', '{listener}',
        ], [
            trim(XditnModule::getModuleListenersNamespace($this->argument('module')), '\\'),

            $this->getListenerName()])->toString());

        if (File::exists($file)) {
            $this->info($file.' has been created');
        } else {
            $this->error($file.' create failed');
        }
    }

    protected function getListenerFile(): string
    {
        return $this->getListenerName().'.php';
    }

    protected function getListenerName(): string
    {
        return Str::of($this->argument('name'))
            ->whenContains('Listener', function ($str) {
                return $str;
            }, function ($str) {
                return $str->append('Listener');
            })->ucfirst()->toString();
    }

    /**
     * get stub content.
     */
    protected function getStubContent(): string
    {
        return File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'listener.stub');
    }
}
