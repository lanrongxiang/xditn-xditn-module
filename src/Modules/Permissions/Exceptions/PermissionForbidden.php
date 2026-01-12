<?php

declare(strict_types=1);

namespace Modules\Permissions\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use XditnModule\Enums\Code;
use XditnModule\Exceptions\XditnModuleException;

class PermissionForbidden extends XditnModuleException
{
    protected $message = 'permission forbidden';

    protected $code = Code::PERMISSION_FORBIDDEN;

    public function statusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
