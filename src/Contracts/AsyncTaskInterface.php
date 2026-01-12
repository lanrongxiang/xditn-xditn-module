<?php

declare(strict_types=1);

namespace XditnModule\Contracts;

interface AsyncTaskInterface
{
    /**
     * push task.
     */
    public function push(): mixed;

    /**
     * run task.
     */
    public function run(array $params): mixed;
}
