<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

class WebhookException extends XditnModuleException
{
    protected $code = Code::WEBHOOK_FAILED;
}
