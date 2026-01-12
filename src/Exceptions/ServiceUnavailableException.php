<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

/**
 * 服务不可用异常.
 *
 * 当服务暂时不可用时抛出此异常（如维护中、依赖服务故障等）。
 *
 * 使用示例：
 * ```php
 * throw new ServiceUnavailableException('支付服务暂时不可用', 'payment');
 * ```
 */
class ServiceUnavailableException extends XditnModuleException
{
    protected $code = Code::FAILED;

    /**
     * @param string $message 错误消息
     * @param string|null $service 不可用的服务名称
     * @param int $retryAfter 建议重试等待时间（秒）
     */
    public function __construct(
        string $message = '服务暂时不可用，请稍后再试',
        protected ?string $service = null,
        protected int $retryAfter = 60,
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 503;
    }

    /**
     * 获取不可用的服务名称.
     */
    public function getService(): ?string
    {
        return $this->service;
    }

    /**
     * 获取建议重试等待时间.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function render(): array
    {
        $data = [
            'code' => $this->code,
            'message' => $this->message,
            'retry_after' => $this->retryAfter,
        ];

        if ($this->service !== null) {
            $data['service'] = $this->service;
        }

        return $data;
    }
}
