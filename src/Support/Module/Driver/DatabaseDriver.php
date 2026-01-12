<?php

declare(strict_types=1);

namespace XditnModule\Support\Module\Driver;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use XditnModule\Contracts\ModuleRepositoryInterface;
use XditnModule\Enums\Status;
use XditnModule\Exceptions\FailedException;
use XditnModule\XditnModule;

/**
 * DatabaseDriver.
 */
class DatabaseDriver implements ModuleRepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->createModuleModel();
    }

    /**
     * all.
     */
    public function all(array $search): Collection
    {
        return $this->model::query()
            ->when($search['title'] ?? false, function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search['title'].'%');
            })->get();
    }

    /**
     * create module json.
     */
    public function create(array $module): bool|int
    {
        $this->hasSameModule($module);

        return $this->model->save([
            'title' => $module['title'],
            'path' => $module['path'],
            'description' => $module['desc'],
            'keywords' => $module['keywords'],
            'provider' => sprintf('\\%s%s', XditnModule::getModuleNamespace($module['name']), ucfirst($module['name']).'ServiceProvider'),
        ]);
    }

    /**
     * module info.
     */
    public function show(string $name): Collection
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * update module json.
     */
    public function update(string $name, array $module): bool|int
    {
        return $this->model->where('name', $name)

            ->update([
                'title' => $module['title'],
                'name' => $module['path'],
                'path' => $module['path'],
                'description' => $module['desc'],
                'keywords' => $module['keywords'],
            ]);
    }

    /**
     * delete module json.
     */
    public function delete(string $name): bool|int
    {
        return $this->model->where('name', $name)->delete();
    }

    /**
     * disable or enable.
     */
    public function disOrEnable($name): bool|int
    {
        $module = $this->show($name);

        $module->enable = (int) $module->enable;

        return $module->save();
    }

    /**
     * get enabled.
     */
    public function getEnabled(): Collection
    {
        return $this->model->where('enable', Status::Enable->value())->get();
    }

    /**
     * enabled.
     */
    public function enabled(string $moduleName): bool
    {
        return $this->getEnabled()->pluck('name')->contains($moduleName);
    }

    protected function hasSameModule(array $module): void
    {
        if ($this->model->where('name', $module['name'])->first()) {
            throw new FailedException(sprintf('Module [%s] has been created', $module['name']));
        }
    }

    /**
     * create model.
     */
    protected function createModuleModel(): Model
    {
        return new class() extends Model {
            protected $table;

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);

                $this->table = Container::getInstance()->make('config')->get('xditn.module.driver.table_name');
            }
        };
    }
}
