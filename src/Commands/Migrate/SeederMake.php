<?php

namespace XditnModule\Commands\Migrate;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpParser\Node\Name;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class SeederMake extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:seeder {module} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make module seeder';

    /**
     * @throws \Exception
     *
     * @author XditnModule
     *
     * @time 2021年08月01日
     */
    public function handle(): void
    {
        $seederPath = XditnModule::getModuleSeederPath($this->argument('module'));

        $file = $seederPath.$this->getSeederName().'.php';

        if (File::exists($file)) {
            $answer = $this->ask($file.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                exit;
            }
        }

        File::put($file, $this->getSeederContent());

        if (File::exists($file)) {
            $this->info($file.' has been created');
        } else {
            $this->error($file.' create failed');
        }
    }

    /**
     * seeder content.
     *
     * @throws \Exception
     */
    protected function getSeederContent(): string
    {
        return File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'seeder.stub');
    }

    /**
     * seeder name.
     */
    protected function getSeederName(): string
    {
        return Str::of($this->argument('name'))->ucfirst()->toString();
    }
}
