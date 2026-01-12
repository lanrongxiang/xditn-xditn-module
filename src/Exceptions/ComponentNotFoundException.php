<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

class ComponentNotFoundException extends XditnModuleException
{
    protected $code = Code::COMPONENT_NOT_FOUND;
}
