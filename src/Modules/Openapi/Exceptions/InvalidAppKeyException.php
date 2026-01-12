<?php

namespace Modules\Openapi\Exceptions;

use Modules\Openapi\Enums\Code;

class InvalidAppKeyException extends OpenapiException
{
    protected $code = Code::INVALID_APP_KEY;
}
