<?php

namespace Modules\Openapi\Facade;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;
use Modules\Openapi\Enums\Code;

/**
 * openapi response facade.
 *
 * @method static JsonResponse success(mixed $data, string $message = 'success', Code $code = Code::SUCCESS)
 * @method static JsonResponse error(string $message = 'api error', int|Code $code = Code::FAILED)
 * @method static JsonResponse paginate(LengthAwarePaginator $paginator, string $message = 'success', Code $code = Code::SUCCESS)
 *
 * @mixin \Modules\Openapi\Support\OpenapiResponse
 */
class OpenapiResponse extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return \Modules\Openapi\Support\OpenapiResponse::class;
    }
}
