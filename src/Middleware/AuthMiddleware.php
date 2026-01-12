<?php

namespace XditnModule\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use XditnModule\Enums\Code;
use XditnModule\Events\User as UserEvent;
use XditnModule\Exceptions\FailedException;
use XditnModule\Exceptions\TokenExpiredException;
use XditnModule\Facade\Admin;

class AuthMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        try {
            $user = Admin::auth();
        } catch (AuthenticationException $e) {
            throw new FailedException('身份认证过期或失败', Code::LOST_LOGIN);
        } catch (TokenExpiredException $e) {
            throw new FailedException('Token 已过期', Code::LOST_LOGIN);
        }

        if ($user) {
            Event::dispatch(new UserEvent($user));
        }

        return $next($request);
    }
}
