<?php

declare(strict_types=1);

namespace XditnModule\Commands\Create;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use XditnModule\Commands\XditnModuleCommand;
use XditnModule\XditnModule;

class Crud extends XditnModuleCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xditn:module:make:crud {module} {name} {--t= : the model of table name} {--subgroup= : API subgroup name} {--subgroup-description= : API subgroup description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create complete CRUD code (Controller, Model, Request, Service, Test)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        $tableName = $this->option('t') ?: Str::of($name)->snake()->lcfirst()->toString();
        $subgroup = $this->option('subgroup') ?: Str::of($name)->plural()->toString();
        $subgroupDescription = $this->option('subgroup-description') ?: Str::of($name)->plural()->append('管理')->toString();

        // 检查表是否存在
        if (!Schema::hasTable($tableName)) {
            $this->error("Table [{$tableName}] not found");
            exit;
        }

        $this->info("Generating CRUD code for {$name}...");

        // 生成 Model
        $this->generateModel($module, $name, $tableName);

        // 生成 Request
        $this->generateRequest($module, $name, 'Store');
        $this->generateRequest($module, $name, 'Update');

        // 生成 Service
        $this->generateService($module, $name);

        // 生成 Controller
        $this->generateController($module, $name, $subgroup, $subgroupDescription);

        // 生成 Test
        $this->generateTest($module, $name);

        $this->info('CRUD code generated successfully!');
    }

    /**
     * Generate Model.
     */
    protected function generateModel(string $module, string $name, string $tableName): void
    {
        $modelPath = XditnModule::getModuleModelPath($module);
        $modelFile = $modelPath.$name.'.php';
        $modelNamespace = trim(XditnModule::getModuleModelNamespace($module), '\\');

        if (File::exists($modelFile) && !$this->confirmOverwrite($modelFile)) {
            return;
        }

        $fillable = $this->getFillable($tableName);
        $stub = File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'model.stub');

        $content = Str::of($stub)
            ->replace('{namespace}', $modelNamespace)
            ->replace('{model}', $name)
            ->replace('{table}', $tableName)
            ->replace('{fillable}', $fillable)
            ->toString();

        File::put($modelFile, $content);
        $this->info("Model created: {$modelFile}");
    }

    /**
     * Generate Request.
     */
    protected function generateRequest(string $module, string $name, string $type): void
    {
        $requestPath = XditnModule::getModuleRequestPath($module);
        $requestName = $type.$name.'Request';
        $requestFile = $requestPath.$requestName.'.php';
        $requestNamespace = trim(XditnModule::getModuleRequestNamespace($module), '\\');

        if (File::exists($requestFile) && !$this->confirmOverwrite($requestFile)) {
            return;
        }

        $stub = File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'request.stub');

        $content = Str::of($stub)
            ->replace('{namespace}', $requestNamespace)
            ->replace('{request}', $requestName)
            ->toString();

        File::put($requestFile, $content);
        $this->info("Request created: {$requestFile}");
    }

    /**
     * Generate Service.
     */
    protected function generateService(string $module, string $name): void
    {
        $servicePath = XditnModule::getModulePath($module).'Services'.DIRECTORY_SEPARATOR;
        XditnModule::makeDir($servicePath);

        $serviceName = $name.'Service';
        $serviceFile = $servicePath.$serviceName.'.php';
        $serviceNamespace = trim(XditnModule::getModuleNamespace($module), '\\').'Services';
        $modelNamespace = trim(XditnModule::getModuleModelNamespace($module), '\\');

        if (File::exists($serviceFile) && !$this->confirmOverwrite($serviceFile)) {
            return;
        }

        $modelVar = Str::of($name)->lcfirst()->toString();
        $stub = File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'service.stub');

        $content = Str::of($stub)
            ->replace('{namespace}', $serviceNamespace)
            ->replace('{service}', $serviceName)
            ->replace('{modelNamespace}', $modelNamespace)
            ->replace('{model}', $name)
            ->replace('{modelVar}', $modelVar)
            ->toString();

        File::put($serviceFile, $content);
        $this->info("Service created: {$serviceFile}");
    }

    /**
     * Generate Controller.
     */
    protected function generateController(string $module, string $name, string $subgroup, string $subgroupDescription): void
    {
        $controllerPath = XditnModule::getModuleControllerPath($module);
        $controllerName = $name.'Controller';
        $controllerFile = $controllerPath.$controllerName.'.php';
        $controllerNamespace = trim(XditnModule::getModuleControllerNamespace($module), '\\');
        $requestNamespace = trim(XditnModule::getModuleRequestNamespace($module), '\\');
        $modelNamespace = trim(XditnModule::getModuleModelNamespace($module), '\\');

        if (File::exists($controllerFile) && !$this->confirmOverwrite($controllerFile)) {
            return;
        }

        $modelVar = Str::of($name)->lcfirst()->toString();
        $stub = File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'controller.stub');

        $content = Str::of($stub)
            ->replace('{namespace}', $controllerNamespace)
            ->replace('{controller}', $controllerName)
            ->replace('{requestNamespace}', $requestNamespace)
            ->replace('{storeRequest}', 'Store'.$name.'Request')
            ->replace('{updateRequest}', 'Update'.$name.'Request')
            ->replace('{modelNamespace}', $modelNamespace)
            ->replace('{model}', $name)
            ->replace('{subgroup}', $subgroup)
            ->replace('{subgroupDescription}', $subgroupDescription)
            ->toString();

        File::put($controllerFile, $content);
        $this->info("Controller created: {$controllerFile}");
    }

    /**
     * Generate Test.
     */
    protected function generateTest(string $module, string $name): void
    {
        $testPath = base_path('tests'.DIRECTORY_SEPARATOR.'Unit'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR);
        XditnModule::makeDir($testPath);

        $testName = $name.'ServiceTest';
        $testFile = $testPath.$testName.'.php';
        $testNamespace = 'Tests\Unit\Services';
        $modelNamespace = trim(XditnModule::getModuleModelNamespace($module), '\\');

        if (File::exists($testFile) && !$this->confirmOverwrite($testFile)) {
            return;
        }

        $modelVar = Str::of($name)->lcfirst()->toString();
        $stub = File::get(dirname(__DIR__).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.'test.stub');

        $content = Str::of($stub)
            ->replace('{namespace}', $testNamespace)
            ->replace('{test}', $testName)
            ->replace('{modelNamespace}', $modelNamespace)
            ->replace('{model}', $name)
            ->replace('{modelVar}', $modelVar)
            ->toString();

        File::put($testFile, $content);
        $this->info("Test created: {$testFile}");
    }

    /**
     * Get fillable fields from table.
     */
    protected function getFillable(string $tableName): string
    {
        $fillable = Str::of('');

        foreach (getTableColumns($tableName) as $column) {
            $fillable = $fillable->append("'{$column}', ");
        }

        return $fillable->trim(',')->toString();
    }

    /**
     * Confirm overwrite file.
     */
    protected function confirmOverwrite(string $file): bool
    {
        $answer = $this->askFor("{$file} already exists, Do you want to replace it?", 'N', true);

        return Str::of($answer)->lower()->exactly('y');
    }
}
