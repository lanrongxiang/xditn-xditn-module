<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

final class ImmutablePropertyException extends XditnModuleException
{
    public function __construct(string $property)
    {
        parent::__construct("Cannot update immutable property: {$property}");
    }
}
