<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use XditnModule\Middleware\RequestTracingMiddleware;

class RequestTracingMiddlewareTest extends TestCase
{
    protected RequestTracingMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new RequestTracingMiddleware();
    }

    public function test_generates_trace_id(): void
    {
        config(['xditn.tracing.enabled' => true]);
        config(['xditn.tracing.header' => 'X-Trace-Id']);

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertTrue($response->headers->has('X-Trace-Id'));
        $this->assertNotEmpty($response->headers->get('X-Trace-Id'));
    }

    public function test_uses_provided_trace_id(): void
    {
        config(['xditn.tracing.enabled' => true]);
        config(['xditn.tracing.header' => 'X-Trace-Id']);

        $traceId = 'test-trace-id-123';
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Trace-Id', $traceId);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals($traceId, $response->headers->get('X-Trace-Id'));
    }

    public function test_stores_trace_id_in_request_attributes(): void
    {
        config(['xditn.tracing.enabled' => true]);
        config(['xditn.tracing.header' => 'X-Trace-Id']);

        $request = Request::create('/api/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            $this->assertTrue($req->attributes->has('trace_id'));
            $this->assertNotEmpty($req->attributes->get('trace_id'));

            return new Response('OK');
        });
    }

    public function test_disabled_tracing(): void
    {
        config(['xditn.tracing.enabled' => false]);

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-Trace-Id'));
    }

    public function test_custom_header_name(): void
    {
        config(['xditn.tracing.enabled' => true]);
        config(['xditn.tracing.header' => 'X-Request-Id']);

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertTrue($response->headers->has('X-Request-Id'));
    }
}
