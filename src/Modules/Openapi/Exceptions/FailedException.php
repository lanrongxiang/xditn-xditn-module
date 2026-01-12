<?php

namespace Modules\Openapi\Exceptions;

use Modules\Openapi\Enums\Code;

class FailedException extends OpenapiException
{
    //
    protected $code = Code::FAILED;
}
