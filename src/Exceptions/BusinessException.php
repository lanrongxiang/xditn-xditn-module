<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

/**
 * 业务异常.
 *
 * 当业务规则检查失败时抛出此异常。
 *
 * 使用示例：
 * ```php
 * throw new BusinessException('余额不足', ['required' => 100, 'current' => 50]);
 * ```
 */
class BusinessException extends XditnModuleException
{
    protected $code = Code::FAILED;

    /**
     * @param string $message 错误消息
     * @param array<string, mixed> $context 业务上下文数据
     */
    public function __construct(
        string $message = '业务处理失败',
        protected array $context = [],
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 400;
    }

    /**
     * 获取业务上下文.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function render(): array
    {
        $data = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if (!empty($this->context)) {
            $data['context'] = $this->context;
        }

        return $data;
    }
}
