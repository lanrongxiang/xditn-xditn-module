<?php

declare(strict_types=1);

namespace XditnModule;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use XditnModule\Contracts\ModuleRepositoryInterface;
use XditnModule\Support\Module\Installer;

final class XditnModule
{
    public const VERSION = '1.0.0';

    /**
     * Get version.
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    public static function moduleRoot(): string
    {
        return config('xditn.module.root', 'modules/');
    }

    /**
     * 获取包内模块路径.
     */
    public static function packageModulesPath(): string
    {
        return dirname(__DIR__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR;
    }

    /**
     * module root path.
     */
    public static function moduleRootPath(): string
    {
        return self::makeDir(base_path(self::moduleRoot()).DIRECTORY_SEPARATOR);
    }

    /**
     * make dir.
     */
    public static function makeDir(string $dir): string
    {
        if (!File::isDirectory($dir) && !File::makeDirectory($dir, 0777, true)) {
            throw new \RuntimeException(sprintf('Directory %s created Failed', $dir));
        }

        return $dir;
    }

    /**
     * module dir.
     * 优先查找包内模块，如果不存在则查找项目目录.
     */
    public static function getModulePath(string $module, bool $make = true): string
    {
        // 首先检查包内是否存在该模块
        $packageModulePath = self::packageModulesPath().ucfirst($module).DIRECTORY_SEPARATOR;
        if (File::isDirectory($packageModulePath)) {
            return $packageModulePath;
        }

        // 如果包内不存在，则使用项目目录
        if ($make) {
            return self::makeDir(self::moduleRootPath().ucfirst($module).DIRECTORY_SEPARATOR);
        }

        return self::moduleRootPath().ucfirst($module).DIRECTORY_SEPARATOR;
    }

    /**
     * delete module path.
     */
    public static function deleteModulePath(string $module): bool
    {
        if (self::isModulePathExist($module)) {
            File::deleteDirectory(self::getModulePath($module));
        }

        return true;
    }

    /**
     * module path exists.
     * 检查包内或项目目录是否存在该模块.
     */
    public static function isModulePathExist(string $module): bool
    {
        // 检查包内模块
        $packageModulePath = self::packageModulesPath().ucfirst($module).DIRECTORY_SEPARATOR;
        if (File::isDirectory($packageModulePath)) {
            return true;
        }

        // 检查项目模块
        return File::isDirectory(self::moduleRootPath().ucfirst($module).DIRECTORY_SEPARATOR);
    }

    /**
     * module migration dir.
     */
    public static function getModuleMigrationPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR);
    }

    /**
     * module seeder dir.
     */
    public static function getModuleSeederPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'database'.DIRECTORY_SEPARATOR.'seeder'.DIRECTORY_SEPARATOR);
    }

    /**
     * Get modules directories.
     * 合并包内模块和项目模块目录.
     */
    public static function getModulesPath(): array
    {
        $paths = [];

        // 包内模块
        if (File::isDirectory(self::packageModulesPath())) {
            $paths = array_merge($paths, File::directories(self::packageModulesPath()));
        }

        // 项目模块目录
        if (File::isDirectory(self::moduleRootPath())) {
            $paths = array_merge($paths, File::directories(self::moduleRootPath()));
        }

        return array_unique($paths);
    }

    /**
     * Get module root namespace.
     */
    public static function getModuleRootNamespace(): string
    {
        return config('xditn.module.namespace', 'Modules').'\\';
    }

    /**
     * Get module namespace.
     */
    public static function getModuleNamespace(string $moduleName): string
    {
        if (!self::isModulePathExist($moduleName)) {
            return ltrim($moduleName, '\\').'\\';
        }

        return self::getModuleRootNamespace().ucfirst($moduleName).'\\';
    }

    /**
     * Get model namespace.
     */
    public static function getModuleModelNamespace(string $moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Models\\';
    }

    /**
     * Get service provider namespace.
     */
    public static function getModuleServiceProviderNamespace(string $moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Providers\\';
    }

    public static function getModuleServiceProvider(string $moduleName): string
    {
        return self::getModuleServiceProviderNamespace($moduleName).ucfirst($moduleName).'ServiceProvider';
    }

    /**
     * Get controller namespace.
     */
    public static function getModuleControllerNamespace(string $moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Http\\Controllers\\';
    }

    /**
     * Get request namespace.
     */
    public static function getModuleRequestNamespace(string $moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Http\\Requests\\';
    }

    /**
     * Get events namespace.
     */
    public static function getModuleEventsNamespace(string $moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Events\\';
    }

    /**
     * Get listeners namespace.
     */
    public static function getModuleListenersNamespace(string $moduleName): string
    {
        return self::getModuleNamespace($moduleName).'Listeners\\';
    }

    /**
     * module provider dir.
     */
    public static function getModuleProviderPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Providers'.DIRECTORY_SEPARATOR);
    }

    /**
     * module model dir.
     */
    public static function getModuleModelPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Models'.DIRECTORY_SEPARATOR);
    }

    /**
     * module controller dir.
     */
    public static function getModuleControllerPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR);
    }

    /**
     * module request dir.
     */
    public static function getModuleRequestPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Http'.DIRECTORY_SEPARATOR.'Requests'.DIRECTORY_SEPARATOR);
    }

    /**
     * module request dir.
     */
    public static function getModuleEventPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Events'.DIRECTORY_SEPARATOR);
    }

    /**
     * module request dir.
     */
    public static function getModuleListenersPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Listeners'.DIRECTORY_SEPARATOR);
    }

    /**
     * commands path.
     */
    public static function getCommandsPath(string $module): string
    {
        return self::makeDir(self::getModulePath($module).'Commands'.DIRECTORY_SEPARATOR);
    }

    /**
     * commands namespace.
     */
    public static function getCommandsNamespace(string $module): string
    {
        return self::getModuleNamespace($module).'Commands\\';
    }

    /**
     * module route.
     */
    public static function getModuleRoutePath(string $module, string $routeName = 'route.php'): string
    {
        $path = self::getModulePath($module).'routes'.DIRECTORY_SEPARATOR;

        self::makeDir($path);

        return $path.$routeName;
    }

    /**
     * module route.php exists.
     */
    public static function isModuleRouteExists(string $module): bool
    {
        return File::exists(self::getModuleRoutePath($module));
    }

    /**
     * relative path.
     */
    public static function getModuleRelativePath(string $path): string
    {
        return Str::replaceFirst(base_path(), '.', $path);
    }

    public static function getModuleInstaller(string $module): Installer
    {
        $installer = self::getModuleNamespace($module).'Installer';

        if (class_exists($installer)) {
            return app($installer);
        }

        throw new \RuntimeException("Installer [$installer] Not Found");
    }

    /**
     * Parse module info from route action.
     */
    public static function parseFromRouteAction(): array
    {
        $routeAction = Route::currentRouteAction();
        if (!$routeAction) {
            return ['', '', ''];
        }

        [$controllerNamespace, $action] = explode('@', $routeAction);

        $controllerNamespace = Str::of($controllerNamespace)
            ->lower()
            ->remove('controller')
            ->explode('\\');

        $controller = $controllerNamespace->pop() ?? '';
        $module = $controllerNamespace->get(1) ?? '';

        return [$module, $controller, $action];
    }

    /**
     * Get controller actions.
     *
     * @throws \ReflectionException
     */
    public static function getControllerActions(string $module, string $controller): array
    {
        $controllerClass = self::getModuleControllerNamespace($module)
            .Str::of($controller)
                ->ucfirst()
                ->append('Controller')
                ->toString();

        if (!class_exists($controllerClass)) {
            return [];
        }

        $reflectionClass = new \ReflectionClass($controllerClass);
        $actions = [];
        $currentClassName = $reflectionClass->getName();

        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->isPublic()
                && !$method->isConstructor()
                && $method->getDeclaringClass()->getName() === $currentClassName
            ) {
                $actions[] = $method->getName();
            }
        }

        return $actions;
    }

    /**
     * @throws BindingResolutionException
     */
    public static function getAllModules(array $search = []): mixed
    {
        return app()->make(ModuleRepositoryInterface::class)->all($search);
    }

    /**
     * Get all module service providers.
     */
    public static function getAllProviders(): array
    {
        $providers = [];

        // 包内模块
        if (File::isDirectory(self::packageModulesPath())) {
            foreach (File::directories(self::packageModulesPath()) as $dir) {
                $moduleName = pathinfo($dir, PATHINFO_BASENAME);
                $provider = self::getModuleServiceProvider($moduleName);

                if (class_exists($provider)) {
                    $providers[] = $provider;
                }
            }
        }

        // 项目模块
        if (File::isDirectory(self::moduleRootPath())) {
            foreach (File::directories(self::moduleRootPath()) as $dir) {
                $moduleName = pathinfo($dir, PATHINFO_BASENAME);
                $provider = self::getModuleServiceProvider($moduleName);

                if (class_exists($provider) && !in_array($provider, $providers)) {
                    $providers[] = $provider;
                }
            }
        }

        return $providers;
    }

    /**
     * Get module provider by directory name.
     */
    public static function getModuleProviderBy(string $dirName): ?string
    {
        $modulePath = self::moduleRootPath().lcfirst($dirName);

        if (!is_dir($modulePath)) {
            return null;
        }

        $provider = self::getModuleServiceProvider(basename($modulePath));

        return class_exists($provider) ? $provider : null;
    }
}
