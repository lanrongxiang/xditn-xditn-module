<?php

namespace XditnModule\Commands\Migrate;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class MigrateFresh extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:migrate:fresh {module} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fresh xditnmodule migrations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $module = $this->argument('module');

        if (!File::isDirectory(XditnModule::getModuleMigrationPath($module))) {
            Artisan::call('migration:fresh', [
                '--path' => XditnModule::getModuleRelativePath(XditnModule::getModuleMigrationPath($module)),

                '--force' => $this->option('force'),
            ]);
        } else {
            $this->error('No migration files in module');
        }
    }
}
