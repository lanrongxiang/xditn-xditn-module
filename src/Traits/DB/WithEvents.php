<?php

declare(strict_types=1);

namespace XditnModule\Traits\DB;

use Closure;

/**
 * base operate.
 */
trait WithEvents
{
    protected ?Closure $beforeGetList = null;

    protected ?Closure $afterFirstBy = null;

    /**
     * @return $this
     */
    public function setBeforeGetList(Closure $closure): static
    {
        $this->beforeGetList = $closure;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAfterFirstBy(Closure $closure): static
    {
        $this->afterFirstBy = $closure;

        return $this;
    }
}
