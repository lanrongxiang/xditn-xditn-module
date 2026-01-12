<?php

namespace Modules\User\Services;

use Illuminate\Support\Facades\Event;
use Modules\User\Events\Login;
use Modules\User\Services\Login\Factory;
use XditnModule\Exceptions\FailedException;

class Auth
{
    public function attempt(array $params): array
    {
        try {
            $auth = Factory::make($params);

            $user = $auth->auth($params);

            // 用户是否禁用
            if ($user->isDisable()) {
                throw new FailedException('⚠该账户已被禁用');
            }

            $expiration = config('sanctum.expiration');
            // 如果 expiration 是字符串，先转换为数字；如果是 null 或空，则返回 null
            $expiresAt = null;
            if ($expiration !== null && $expiration !== '') {
                $expirationMinutes = is_numeric($expiration) ? (int) $expiration : 0;
                if ($expirationMinutes > 0) {
                    $expiresAt = now()->addMinutes($expirationMinutes);
                }
            }

            $token = $user->createToken('token', expiresAt: $expiresAt)
                ->plainTextToken;

            // 登录成功事件
            Event::dispatch(new Login($user, $token));

            return compact('token');
        } catch (\Exception|\Throwable $e) {
            // 登录失败日志
            Event::dispatch(new Login(null));
            throw new FailedException($e->getMessage());
        }
    }
}
