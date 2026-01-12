<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

/**
 * 验证异常.
 *
 * 当请求数据验证失败时抛出此异常。
 *
 * 使用示例：
 * ```php
 * throw new ValidationException('邮箱格式不正确', ['email' => '邮箱格式不正确']);
 * ```
 */
class ValidationException extends XditnModuleException
{
    protected $code = Code::VALIDATE_FAILED;

    /**
     * @param string $message 错误消息
     * @param array<string, string|array<string>> $errors 验证错误详情
     */
    public function __construct(
        string $message = '验证失败',
        protected array $errors = [],
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 422;
    }

    /**
     * 获取验证错误详情.
     *
     * @return array<string, string|array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }
}
