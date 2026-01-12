<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

/**
 * 认证异常.
 *
 * 当用户身份认证失败时抛出此异常。
 *
 * 使用示例：
 * ```php
 * throw new AuthenticationException('登录已过期，请重新登录');
 * ```
 */
class AuthenticationException extends XditnModuleException
{
    protected $code = Code::LOST_LOGIN;

    public function __construct(
        string $message = '身份认证失败',
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 401;
    }
}
