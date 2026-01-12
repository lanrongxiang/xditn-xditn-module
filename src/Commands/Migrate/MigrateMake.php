<?php

namespace XditnModule\Commands\Migrate;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class MigrateMake extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:migration {module : The module of the migration created at}
        {table : The name of the table to migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create module migration';

    public function handle(): void
    {
        $migrationPath = XditnModule::getModuleMigrationPath($this->argument('module'));

        $file = $migrationPath.$this->getMigrationFile();

        File::put($file, Str::of($this->getStubContent())->replace(
            '{table}',
            $this->getTable()
        )->toString());

        if (File::exists($file)) {
            $this->info($file.' has been created');
        } else {
            $this->error($file.' create failed');
        }
    }

    protected function getMigrationFile(): string
    {
        return date('Y_m_d_His').'_'.$this->getTable().'.php';
    }

    protected function getTable(): string
    {
        return Str::of($this->argument('table'))->ucfirst()->snake()->lower()->toString();
    }

    /**
     * get stub content.
     */
    protected function getStubContent(): string
    {
        return File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'migration.stub');
    }
}
