<?php

// +----------------------------------------------------------------------
// | XditnModule [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2022 https://XditnModule.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/XditnModule/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace XditnModule\Base;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Routing\Controller;
use XditnModule\Enums\Code;
use XditnModule\Exceptions\FailedException;
use XditnModule\Facade\Admin;

/**
 * base xditn module controller.
 */
abstract class XditnModuleController extends Controller
{
    /**
     * Get current login user.
     *
     * @throws AuthenticationException
     */
    protected function getLoginUser(?string $guard = null, ?string $field = null): mixed
    {
        try {
            $user = Admin::currentLoginUser();

            if ($field) {
                return $user->getAttribute($field);
            }

            return $user;
        } catch (\Throwable $e) {
            throw new FailedException('登录失效, 请重新登录', Code::LOST_LOGIN);
        }
    }

    /**
     * Get current login user ID.
     *
     * @throws AuthenticationException
     */
    protected function getLoginUserId(?string $guard = null): mixed
    {
        return $this->getLoginUser($guard, 'id');
    }

}
