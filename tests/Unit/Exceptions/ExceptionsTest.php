<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Tests\TestCase;
use XditnModule\Enums\Code;
use XditnModule\Exceptions\AuthenticationException;
use XditnModule\Exceptions\AuthorizationException;
use XditnModule\Exceptions\BusinessException;
use XditnModule\Exceptions\RateLimitException;
use XditnModule\Exceptions\ResourceNotFoundException;
use XditnModule\Exceptions\ServiceUnavailableException;
use XditnModule\Exceptions\ValidationException;

class ExceptionsTest extends TestCase
{
    public function test_validation_exception(): void
    {
        $errors = ['email' => '邮箱格式不正确'];
        $exception = new ValidationException('验证失败', $errors);

        $this->assertEquals('验证失败', $exception->getMessage());
        $this->assertEquals(422, $exception->statusCode());
        $this->assertEquals($errors, $exception->getErrors());

        $render = $exception->render();
        $this->assertArrayHasKey('errors', $render);
        $this->assertEquals($errors, $render['errors']);
    }

    public function test_authentication_exception(): void
    {
        $exception = new AuthenticationException('登录已过期');

        $this->assertEquals('登录已过期', $exception->getMessage());
        $this->assertEquals(401, $exception->statusCode());
        $this->assertEquals(Code::LOST_LOGIN->value(), $exception->getCode());
    }

    public function test_authorization_exception(): void
    {
        $exception = new AuthorizationException('权限不足');

        $this->assertEquals('权限不足', $exception->getMessage());
        $this->assertEquals(403, $exception->statusCode());
        $this->assertEquals(Code::PERMISSION_FORBIDDEN->value(), $exception->getCode());
    }

    public function test_resource_not_found_exception(): void
    {
        $exception = new ResourceNotFoundException('用户不存在', 'User', 123);

        $this->assertEquals('用户不存在', $exception->getMessage());
        $this->assertEquals(404, $exception->statusCode());
        $this->assertEquals('User', $exception->getResource());
        $this->assertEquals(123, $exception->getResourceId());

        $render = $exception->render();
        $this->assertArrayHasKey('resource', $render);
        $this->assertArrayHasKey('resource_id', $render);
    }

    public function test_business_exception(): void
    {
        $context = ['required' => 100, 'current' => 50];
        $exception = new BusinessException('余额不足', $context);

        $this->assertEquals('余额不足', $exception->getMessage());
        $this->assertEquals(400, $exception->statusCode());
        $this->assertEquals($context, $exception->getContext());

        $render = $exception->render();
        $this->assertArrayHasKey('context', $render);
        $this->assertEquals($context, $render['context']);
    }

    public function test_rate_limit_exception(): void
    {
        $exception = new RateLimitException('请求过于频繁', retryAfter: 120, maxAttempts: 100);

        $this->assertEquals('请求过于频繁', $exception->getMessage());
        $this->assertEquals(429, $exception->statusCode());
        $this->assertEquals(120, $exception->getRetryAfter());
        $this->assertEquals(100, $exception->getMaxAttempts());

        $render = $exception->render();
        $this->assertArrayHasKey('retry_after', $render);
        $this->assertArrayHasKey('max_attempts', $render);
    }

    public function test_rate_limit_exception_to_response(): void
    {
        $exception = new RateLimitException('请求过于频繁', retryAfter: 60, maxAttempts: 100);

        $response = $exception->toResponse();

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('60', $response->headers->get('Retry-After'));
        $this->assertEquals('100', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_service_unavailable_exception(): void
    {
        $exception = new ServiceUnavailableException('支付服务不可用', 'payment', 300);

        $this->assertEquals('支付服务不可用', $exception->getMessage());
        $this->assertEquals(503, $exception->statusCode());
        $this->assertEquals('payment', $exception->getService());
        $this->assertEquals(300, $exception->getRetryAfter());

        $render = $exception->render();
        $this->assertArrayHasKey('service', $render);
        $this->assertArrayHasKey('retry_after', $render);
    }

    public function test_exception_with_custom_code(): void
    {
        $exception = new BusinessException('测试', [], Code::FAILED);

        $this->assertEquals(Code::FAILED->value(), $exception->getCode());
    }
}
