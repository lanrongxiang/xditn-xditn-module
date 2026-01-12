<?php

namespace Modules\Openapi\Exceptions;

use Modules\Openapi\Enums\Code;

class RateLimiterException extends OpenapiException
{
    protected $code = Code::RATE_LIMIT;
}
