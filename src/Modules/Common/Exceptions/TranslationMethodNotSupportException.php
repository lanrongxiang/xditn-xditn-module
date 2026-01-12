<?php

declare(strict_types=1);

namespace Modules\Common\Exceptions;

use XditnModule\Exceptions\FailedException;

/**
 * 翻译方法不支持异常.
 */
class TranslationMethodNotSupportException extends FailedException
{
}
