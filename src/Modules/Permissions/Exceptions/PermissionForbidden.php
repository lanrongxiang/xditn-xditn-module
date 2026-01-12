<?php

namespace Modules\Permissions\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use XditnModule\Enums\Code;
use XditnModule\Exceptions\CatchException;

class PermissionForbidden extends CatchException
{
    protected $message = 'permission forbidden';

    protected $code = Code::PERMISSION_FORBIDDEN;

    public function statusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
