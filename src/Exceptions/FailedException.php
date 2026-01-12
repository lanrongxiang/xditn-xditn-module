<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

class FailedException extends XditnModuleException
{
    protected $code = Code::FAILED;
}
