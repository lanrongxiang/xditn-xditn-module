<?php

namespace Modules\Openapi\Exceptions;

use Modules\Openapi\Enums\Code;

class BalanceNotEnoughException extends OpenapiException
{
    protected $code = Code::Balance_NOT_ENOUGH;
}
