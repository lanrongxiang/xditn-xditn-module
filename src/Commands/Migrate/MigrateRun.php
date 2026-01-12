<?php

namespace XditnModule\Commands\Migrate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class MigrateRun extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:migrate {module} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run xditnmodule migrations';

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
        $migrationModel = new class() extends Model {
            protected $table = 'migrations';
        };

        $module = $this->argument('module');

        if (File::isDirectory(XditnModule::getModuleMigrationPath($module))) {
            foreach (File::files(XditnModule::getModuleMigrationPath($module)) as $file) {
                if (!$migrationModel::query()->where('migration', $file->getBasename('.php'))->exists()) {
                    $path = Str::of(XditnModule::getModuleRelativePath(XditnModule::getModuleMigrationPath($module)))

                        ->remove('.')->append($file->getFilename());

                    Artisan::call('migrate', [
                        '--path' => $path,

                        '--force' => $this->option('force'),
                    ], $this->output);
                }
            }
            $this->info('Module migrate success');
        } else {
            $this->error('No migration files in module');
        }
    }
}
