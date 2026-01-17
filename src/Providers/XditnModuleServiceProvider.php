<?php

declare(strict_types=1);

namespace XditnModule\Providers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use XditnModule\Contracts\ModuleRepositoryInterface;
use XditnModule\Exceptions\Handler;
use XditnModule\Support\DB\Query;
use XditnModule\Support\Macros\MacrosRegister;
use XditnModule\Support\Module\ModuleManager;
use XditnModule\XditnModule;

/**
 * XditnModule Service Provider.
 */
class XditnModuleServiceProvider extends ServiceProvider
{
    /**
     * boot.
     *
     * @throws ContainerExceptionInterface
     */
    public function boot(): void
    {
        $this->bootMacros();
        $this->bootDefaultModuleProviders();
        $this->bootModuleProviders();
        $this->registerEvents();
        $this->listenDBLog();
    }

    /**
     * register.
     *
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->registerCommands();
        $this->registerModuleRepository();
        $this->registerExceptionHandler();
        $this->publishConfig();
        $this->publishModuleMigration();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function bootMacros(): void
    {
        $this->app->make(MacrosRegister::class)->boot();
        // 资源路由注册器
        $this->app->bind(ResourceRegistrar::class, \XditnModule\Support\ResourceRegistrar::class);
    }

    /**
     * register commands.
     *
     * @throws ReflectionException
     */
    protected function registerCommands(): void
    {
        loadCommands(dirname(__DIR__).DIRECTORY_SEPARATOR.'Commands', 'XditnModule\\');
    }

    /**
     * bind module repository.
     */
    protected function registerModuleRepository(): void
    {
        // register module manager
        $this->app->singleton(ModuleManager::class, function () {
            return new ModuleManager(fn () => Container::getInstance());
        });

        // register module repository
        $this->app->singleton(ModuleRepositoryInterface::class, function () {
            return $this->app->make(ModuleManager::class)->driver();
        });

        $this->app->alias(ModuleRepositoryInterface::class, 'module');
    }

    /**
     * register events.
     */
    protected function registerEvents(): void
    {
        $listener = config('xditn.module.response.request_handled_listener');

        // 只有配置了监听器才注册
        if ($listener && class_exists($listener)) {
            Event::listen(RequestHandled::class, $listener);
        }
    }

    /**
     * register exception handler.
     */
    protected function registerExceptionHandler(): void
    {
        if (isRequestFromDashboard()) {
            $this->app->singleton(ExceptionHandler::class, function () {
                return new Handler((fn () => Container::getInstance()));
            });
        }
    }

    /**
     * publish config.
     */
    protected function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $from = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'xditn.php';

            $to = config_path('xditn.php');

            $this->publishes([$from => $to], 'xditn-config');
        }
    }

    /**
     * publish module migration.
     */
    protected function publishModuleMigration(): void
    {
        if ($this->app->runningInConsole()) {
            $form = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'2022_11_14_034127_module.php';

            $to = database_path('migrations').DIRECTORY_SEPARATOR.'2022_11_14_034127_module.php';

            $this->publishes([$form => $to], 'xditnmodule-module');
        }
    }

    protected function bootDefaultModuleProviders(): void
    {
        foreach ($this->app['config']->get('xditn.module.default', []) as $module) {
            $provider = XditnModule::getModuleServiceProvider($module);
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * 启动模块服务
     *
     *
     * @throws BindingResolutionException
     */
    protected function bootModuleProviders(): void
    {
        // 如果配置了模块自动加载，则不需要从本地配置中加载
        if (config('xditn.module.autoload')) {
            foreach (XditnModule::getAllProviders() as $provider) {
                $this->app->register($provider);
            }
        } else {
            foreach ($this->app->make(ModuleRepositoryInterface::class)->getEnabled() as $module) {
                if ($provider = XditnModule::getModuleProviderBy($module['path'])) {
                    $this->app->register($provider);
                }
            }
        }

        $this->registerModuleRoutes();
    }

    /**
     * register module routes.
     */
    protected function registerModuleRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            $route = $this->app['config']->get('xditn.route', []);

            if (!empty($route) && isset($route['prefix'])) {
                Route::prefix($route['prefix'])
                    ->middleware($route['middlewares'])
                    ->group($this->app['config']->get('xditn.module.routes'));
            }
        }
    }

    /**
     * listen db log.
     */
    protected function listenDBLog(): void
    {
        if ($this->app['config']->get('xditn.listen_db_log')) {
            Query::listen();

            $this->app->terminating(function () {
                Query::log();
            });
        }
    }

    /**
     * file exist.
     */
    protected function routesAreCached(): bool
    {
        return $this->app->routesAreCached();
    }
}
