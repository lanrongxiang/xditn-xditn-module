<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Tests\TestCase;
use XditnModule\Enums\Code;
use XditnModule\Exceptions\FailedException;

class FailedExceptionTest extends TestCase
{
    public function test_exception_has_correct_code(): void
    {
        $exception = new FailedException('测试错误');

        $this->assertInstanceOf(FailedException::class, $exception);
        // getCode() 返回的是 int 值，不是 Code 枚举
        $this->assertEquals(Code::FAILED->value(), $exception->getCode());
    }

    public function test_exception_message(): void
    {
        $message = '这是一个测试错误';
        $exception = new FailedException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
