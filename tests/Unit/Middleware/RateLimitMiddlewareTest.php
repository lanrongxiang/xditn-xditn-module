<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use XditnModule\Exceptions\RateLimitException;
use XditnModule\Middleware\RateLimitMiddleware;

class RateLimitMiddlewareTest extends TestCase
{
    protected RateLimiter $limiter;

    protected RateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->limiter = $this->app->make(RateLimiter::class);
        $this->middleware = new RateLimitMiddleware($this->limiter);
    }

    public function test_allows_request_within_limit(): void
    {
        config(['xditn.rate_limit.enabled' => true]);
        config(['xditn.rate_limit.max_attempts' => 60]);
        config(['xditn.rate_limit.decay_minutes' => 1]);

        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    public function test_blocks_request_over_limit(): void
    {
        config(['xditn.rate_limit.enabled' => true]);
        config(['xditn.rate_limit.max_attempts' => 1]);
        config(['xditn.rate_limit.decay_minutes' => 1]);

        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        // 第一次请求应该通过
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });
        $this->assertEquals(200, $response->getStatusCode());

        // 第二次请求应该被限流
        $this->expectException(RateLimitException::class);
        $this->middleware->handle($request, function () {
            return new Response('OK');
        });
    }

    public function test_disabled_rate_limit(): void
    {
        config(['xditn.rate_limit.enabled' => false]);

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }

    public function test_custom_rate_limit_parameters(): void
    {
        config(['xditn.rate_limit.enabled' => true]);

        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.200');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 100, 5);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(100, $response->headers->get('X-RateLimit-Limit'));
    }
}
