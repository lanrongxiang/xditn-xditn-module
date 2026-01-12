<?php

// +----------------------------------------------------------------------
// | XditnModule [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://XditnModule.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/XditnModule/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace XditnModule\Contracts;

use Illuminate\Support\Collection;

interface ModuleRepositoryInterface
{
    public function all(array $search): Collection;

    public function create(array $module): bool|int;

    public function show(string $name): Collection;

    public function update(string $name, array $module): bool|int;

    public function delete(string $name): bool|int;

    public function disOrEnable(string $name): bool|int;

    public function getEnabled(): Collection;

    public function enabled(string $moduleName): bool;
}
