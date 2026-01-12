<?php

namespace XditnModule\Commands\Migrate;

use Illuminate\Support\Facades\File;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class SeedRun extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:db:seed {module} {--seeder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run xditnmodule database seeder';

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
        $files = File::allFiles(XditnModule::getModuleSeederPath($this->argument('module')));

        $fileNames = [];

        $seeder = $this->option('seeder');

        if ($seeder) {
            foreach ($files as $file) {
                if (pathinfo($file->getBasename(), PATHINFO_FILENAME) == $seeder) {
                    $class = require_once $file->getRealPath();

                    $seeder = new $class();

                    $seeder->run();
                }
            }
        } else {
            foreach ($files as $file) {
                if (File::exists($file->getRealPath())) {
                    $class = require_once $file->getRealPath();
                    $class = new $class();
                    $class->run();
                }
            }
        }

        $this->info('seed run success');
    }
}
