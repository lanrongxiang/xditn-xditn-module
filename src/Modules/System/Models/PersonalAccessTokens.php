<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use XditnModule\Traits\DB\BaseOperate;
use XditnModule\Traits\DB\ScopeTrait;
use XditnModule\Traits\DB\Trans;
use XditnModule\Traits\DB\WithAttributes;

/**
 * @property $id
 * @property $tokenable_type
 * @property $tokenable_id
 * @property $name
 * @property $token
 * @property $abilities
 * @property $last_used_at
 * @property $expires_at
 * @property $created_at
 * @property $updated_at
 */
class PersonalAccessTokens extends Model
{
    use BaseOperate;
    use ScopeTrait;
    use Trans;
    use WithAttributes;

    protected $table = 'personal_access_tokens';

    protected $fillable = ['id', 'tokenable_type', 'tokenable_id', 'name', 'token', 'abilities', 'last_used_at', 'expires_at', 'created_at', 'updated_at'];

    protected array $fields = ['personal_access_tokens.*'];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i',
        'expires_at' => 'date:Y-m-d H:i',
    ];

    protected function expiresAt(): Attribute
    {
        $expiration = config('sanctum.expiration');

        return new Attribute(
            get: function () use ($expiration) {
                if ($expiration === null || $expiration === '') {
                    return null;
                }

                $expirationMinutes = is_numeric($expiration) ? (int) $expiration : 0;
                if ($expirationMinutes <= 0) {
                    return null;
                }

                return $this->created_at->addMinutes($expirationMinutes)->format('Y-m-d H:i');
            }
        );
    }
}
