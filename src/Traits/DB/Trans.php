<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

use Illuminate\Support\Facades\DB;

/**
 * transaction.
 */
trait Trans
{
    /**
     * begin transaction.
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * commit.
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * rollback.
     */
    public function rollback(): void
    {
        DB::rollBack();
    }

    /**
     * transaction.
     */
    public function transaction(\Closure $closure): mixed
    {
        return DB::transaction($closure);
    }
}
