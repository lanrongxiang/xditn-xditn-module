<?php

namespace Modules\Openapi\Exceptions;

use Modules\Openapi\Enums\Code;

class InvalidTimestampException extends OpenapiException
{
    protected $code = Code::INVALID_TIMESTAMP;
}
