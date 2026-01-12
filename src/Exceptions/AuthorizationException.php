<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

/**
 * 授权异常.
 *
 * 当用户没有权限执行某操作时抛出此异常。
 *
 * 使用示例：
 * ```php
 * throw new AuthorizationException('您没有权限执行此操作');
 * ```
 */
class AuthorizationException extends XditnModuleException
{
    protected $code = Code::PERMISSION_FORBIDDEN;

    public function __construct(
        string $message = '权限不足',
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 403;
    }
}
