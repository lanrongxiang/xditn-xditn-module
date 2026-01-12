<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

class TokenExpiredException extends XditnModuleException
{
    protected $code = Code::TOKEN_EXPIRED;
}
