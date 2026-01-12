<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use XditnModule\Enums\Code;

/**
 * 限流异常.
 *
 * 当请求频率超过限制时抛出此异常。
 *
 * 使用示例：
 * ```php
 * throw new RateLimitException('请求过于频繁', retryAfter: 60, maxAttempts: 100);
 * ```
 */
class RateLimitException extends XditnModuleException
{
    protected $code = Code::FAILED;

    /**
     * @param string $message 错误消息
     * @param int $retryAfter 重试等待时间（秒）
     * @param int $maxAttempts 最大请求次数
     */
    public function __construct(
        string $message = '请求过于频繁，请稍后再试',
        protected int $retryAfter = 60,
        protected int $maxAttempts = 60,
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 429;
    }

    /**
     * 获取重试等待时间.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * 获取最大请求次数.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function render(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'retry_after' => $this->retryAfter,
            'max_attempts' => $this->maxAttempts,
        ];
    }

    /**
     * 获取响应（包含限流头）.
     */
    public function toResponse(): Response
    {
        $response = new Response(
            json_encode($this->render()),
            $this->statusCode(),
            [
                'Content-Type' => 'application/json',
                'Retry-After' => $this->retryAfter,
                'X-RateLimit-Limit' => $this->maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]
        );

        return $response;
    }
}
