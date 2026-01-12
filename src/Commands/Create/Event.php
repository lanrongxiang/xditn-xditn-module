<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class Event extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:event {module} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create xditnmodule event';

    public function handle()
    {
        $eventPath = XditnModule::getModuleEventPath($this->argument('module'));

        $file = $eventPath.$this->getEventFile();

        if (File::exists($file)) {
            $answer = $this->ask($file.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                exit;
            }
        }

        File::put($file, Str::of($this->getStubContent())->replace([
            '{namespace}', '{event}',
        ], [trim(XditnModule::getModuleEventsNamespace($this->argument('module')), '\\'), $this->getEventName()])->toString());

        if (File::exists($file)) {
            $this->info($file.' has been created');
        } else {
            $this->error($file.' create failed');
        }
    }

    protected function getEventFile(): string
    {
        return $this->getEventName().'.php';
    }

    protected function getEventName(): string
    {
        return Str::of($this->argument('name'))
            ->whenContains('Event', function ($str) {
                return $str;
            }, function ($str) {
                return $str->append('Event');
            })->ucfirst()->toString();
    }

    /**
     * get stub content.
     */
    protected function getStubContent(): string
    {
        return File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'event.stub');
    }
}
