<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use XditnModule\Enums\Code;

/**
 * 资源不存在异常.
 *
 * 当请求的资源不存在时抛出此异常。
 *
 * 使用示例：
 * ```php
 * throw new ResourceNotFoundException('用户不存在', 'User', $id);
 * ```
 */
class ResourceNotFoundException extends XditnModuleException
{
    protected $code = Code::FAILED;

    /**
     * @param string $message 错误消息
     * @param string|null $resource 资源类型
     * @param mixed $resourceId 资源 ID
     */
    public function __construct(
        string $message = '资源不存在',
        protected ?string $resource = null,
        protected mixed $resourceId = null,
        int|Code $code = 0
    ) {
        parent::__construct($message, $code);
    }

    public function statusCode(): int
    {
        return 404;
    }

    /**
     * 获取资源类型.
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    /**
     * 获取资源 ID.
     */
    public function getResourceId(): mixed
    {
        return $this->resourceId;
    }

    public function render(): array
    {
        $data = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if ($this->resource !== null) {
            $data['resource'] = $this->resource;
        }

        if ($this->resourceId !== null) {
            $data['resource_id'] = $this->resourceId;
        }

        return $data;
    }
}
