<?php

declare(strict_types=1);

namespace XditnModule\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use XditnModule\Enums\Code;
use XditnModule\Enums\Enum;

abstract class XditnModuleException extends HttpException
{
    protected $code = 0;

    public function __construct(string $message = '', int|Code $code = 0)
    {
        if ($code instanceof Enum) {
            $code = $code->value();
        }

        if ($this->code instanceof Enum && !$code) {
            $code = $this->code->value();
        }

        parent::__construct($this->statusCode(), $message ?: $this->message, null, [], $code);
    }

    /**
     * status code.
     */
    public function statusCode(): int
    {
        return 500;
    }

    /**
     * render.
     */
    public function render(): array
    {
        return [
            'code' => $this->code,

            'message' => $this->message,
        ];
    }
}
