<?php

namespace Modules\Openapi\Exceptions;

use Modules\Openapi\Enums\Code;

class InvalidSignatureException extends OpenapiException
{
    protected $code = Code::INVALID_SIGNATURE;
}
