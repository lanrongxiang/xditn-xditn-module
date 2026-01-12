<?php

namespace XditnModule\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Finder\Finder;
use XditnModule\XditnModule;

abstract class XditnModuleModuleServiceProvider extends ServiceProvider
{
    protected array $events = [];

    protected array $commands = [];

    /**
     * register.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(): void
    {
        $this->registerEvents();
        $this->loadMiddlewares();
        $this->loadModuleRoute();
        $this->loadConfig();
        $this->commands($this->commands);
    }

    protected function registerEvents(): void
    {
        foreach ($this->events as $event => $listener) {
            if (is_array($listener)) {
                foreach ($listener as $val) {
                    Event::listen($event, $val);
                }
            } else {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function loadMiddlewares(): void
    {
        if (!empty($middlewares = $this->middlewares())) {
            $route = $this->app['config']->get('xditn.route', [
                'middlewares' => [],
            ]);

            $route['middlewares'] = array_merge($route['middlewares'], $middlewares);

            $this->app['config']->set('xditn.route', $route);
        }
    }

    /**
     * load module config.
     */
    protected function loadConfig(): void
    {
        if (!is_dir($configPath = $this->configPath())) {
            return;
        }

        $files = [];
        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $files[str_replace('.php', '', $file->getBasename())] = $file->getRealPath();
        }

        // 加载模块配置
        foreach ($files as $name => $file) {
            $this->app->make('config')->set(sprintf('%s.%s', lcfirst($this->moduleName()), $name), require $file);
        }
    }

    protected function middlewares(): array
    {
        return [];
    }

    /**
     * return void.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function loadModuleRoute(): void
    {
        $routes = $this->app['config']->get('xditn.module.routes', []);

        $routes[] = XditnModule::getModuleRoutePath($this->moduleName());

        $this->app['config']->set('xditn.module.routes', $routes);
    }

    /**
     * route path.
     */
    abstract protected function moduleName(): string|array;

    /**
     * module config path.
     */
    protected function configPath(): string
    {
        return XditnModule::getModulePath($this->moduleName()).'config'.DIRECTORY_SEPARATOR;
    }
}
