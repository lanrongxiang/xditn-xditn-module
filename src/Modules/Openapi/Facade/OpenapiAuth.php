<?php

namespace Modules\Openapi\Facade;

use Illuminate\Support\Facades\Facade;
use Modules\Openapi\Models\Users;

/**
 * @method static bool check(string $appKey, string $sign, array $data)
 * @method static Users getUser()
 * @method static int getUserId()
 *
 * @mixin \Modules\Openapi\Support\OpenapiAuth
 */
class OpenapiAuth extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return \Modules\Openapi\Support\OpenapiAuth::class;
    }
}
