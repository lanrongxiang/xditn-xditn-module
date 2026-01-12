<?php

namespace Modules\Openapi\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Openapi\Exceptions\RateLimiterException;
use Modules\Openapi\Facade\OpenapiAuth;

/**
 * 速率限制.
 *
 * Class RateLimiterMiddleware
 */
class RateLimiterMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $executed = RateLimiter::attempt($request->header('app-key'), OpenapiAuth::getUser()->qps, function () {
            return null;
        });

        return $executed ? $next($request) : throw new RateLimiterException();
    }
}
