<?php

declare(strict_types=1);

namespace XditnModule\Support\Module;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use XditnModule\Contracts\ModuleRepositoryInterface;
use XditnModule\Events\Module\Created;
use XditnModule\Events\Module\Creating;
use XditnModule\Events\Module\Deleted;
use XditnModule\Events\Module\Updated;
use XditnModule\Events\Module\Updating;

/**
 * FileDriver.
 */
class ModuleRepository
{
    protected ModuleRepositoryInterface $moduleRepository;

    /**
     * construct.
     */
    public function __construct(ModuleRepositoryInterface $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * all.
     */
    public function all(array $search): Collection
    {
        return $this->moduleRepository->all($search);
    }

    /**
     * create module json.
     */
    public function create(array $module): bool
    {
        $module['name'] = lcfirst($module['path']);

        Event::dispatch(new Creating($module));

        $this->moduleRepository->create($module);

        Event::dispatch(new Created($module));

        return true;
    }

    /**
     * module info.
     *
     * @throws Exception
     */
    public function show(string $name): Collection
    {
        try {
            return $this->moduleRepository->show($name);
        } catch (Exception $e) {
            throw new $e();
        }
    }

    /**
     * update module json.
     */
    public function update(string $name, array $module): bool
    {
        $module['name'] = lcfirst($module['path']);

        Event::dispatch(new Updating($name, $module));

        $this->moduleRepository->update($name, $module);

        Event::dispatch(new Updated($name, $module));

        return true;
    }

    /**
     * delete module json.
     *
     * @throws Exception
     */
    public function delete(string $name): bool
    {
        $module = $this->show($name);

        $this->moduleRepository->delete($name);

        Event::dispatch(new Deleted($module));

        return true;
    }

    /**
     * disable or enable.
     */
    public function disOrEnable(string $name): bool|int
    {
        return $this->moduleRepository->disOrEnable($name);
    }

    /**
     * get enabled.
     */
    public function getEnabled(): Collection
    {
        return $this->moduleRepository->getEnabled();
    }

    /**
     * enabled.
     */
    public function enabled(string $moduleName): bool
    {
        return $this->moduleRepository->enabled($moduleName);
    }
}
