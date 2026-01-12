<?php

namespace Modules\Openapi\Enums;

interface Enum
{
    public function equal(mixed $value): bool;
}
