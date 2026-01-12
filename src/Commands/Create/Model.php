<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class Model extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:model {module} {model} {--t= : the model of table name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create xditnmodule model';

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
     *
     * @return void
     */
    public function handle()
    {
        if (!Schema::hasTable($this->getTableName())) {
            $this->error('Schema ['.$this->getTableName().'] not found');
            exit;
        }

        $modelPath = XditnModule::getModuleModelPath($this->argument('module'));

        $file = $modelPath.$this->getModelFile();

        if (File::exists($file)) {
            $answer = $this->ask($file.' already exists, Did you want replace it?', 'Y');

            if (!Str::of($answer)->lower()->exactly('y')) {
                exit;
            }
        }

        File::put($file, $this->getModelContent());

        if (File::exists($file)) {
            $this->info($file.' has been created');
        } else {
            $this->error($file.' create failed');
        }
    }

    protected function getModelFile(): string
    {
        return $this->getModelName().'.php';
    }

    protected function getModelName(): string
    {
        return Str::of($this->argument('model'))->ucfirst()->toString();
    }

    /**
     * get stub content.
     */
    protected function getStubContent(): string
    {
        return File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'model.stub');
    }

    /**
     * get model content.
     */
    protected function getModelContent(): string
    {
        return Str::of($this->getStubContent())

            ->replace(
                [
                    '{namespace}', '{model}', '{table}', '{fillable}',
                ],
                [
                    $this->getModelNamespace(), $this->getModelName(),

                    $this->getTableName(), $this->getFillable(),
                ]
            )->toString();
    }

    /**
     * get namespace.
     */
    protected function getModelNamespace(): string
    {
        return trim(XditnModule::getModuleModelNamespace($this->argument('module')), '\\');
    }

    /**
     * get table name.
     */
    protected function getTableName(): string
    {
        return $this->option('t') ?

            $this->option('t') :

            Str::of($this->argument('model'))->snake()->lcfirst()->toString();
    }

    protected function getFillable(): string
    {
        $fillable = Str::of('');

        foreach (getTableColumns($this->getTableName()) as $column) {
            $fillable = $fillable->append("'{$column}', ");
        }

        return $fillable->trim(',')->toString();
    }
}
