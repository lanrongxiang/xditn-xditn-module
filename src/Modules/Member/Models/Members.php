<?php

declare(strict_types=1);

namespace Modules\Member\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use XditnModule\Base\CatchModel;

/**
 * @property $id
 * @property $email
 * @property $mobile
 * @property $password
 * @property $avatar
 * @property $username
 * @property $miniapp_openid
 * @property $google_id
 * @property $device_id
 * @property $address_id
 * @property $from
 * @property $status
 * @property $token
 * @property $last_login_at
 * @property $last_login_ip
 * @property $creator_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class Members extends CatchModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, JWTSubject
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasUuids;
    use MustVerifyEmail;

    protected $table = 'members';

    /**
     * 主键类型为字符串（UUID）.
     */
    protected $keyType = 'string';

    /**
     * 主键不自增.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'mobile',
        'password',
        'avatar',
        'username',
        'miniapp_openid',
        'google_id',
        'device_id',
        'token',
        'address_id',
        'from',
        'status',
        'last_login_at',
        'last_login_ip',
        'creator_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected array $fields = ['id', 'email', 'mobile', 'miniapp_openid', 'google_id', 'password', 'avatar', 'username', 'from', 'status', 'last_login_at', 'created_at'];

    protected array $form = ['email', 'mobile', 'password', 'avatar', 'username', 'from'];

    public function __construct()
    {
        parent::__construct();

        $this->searchable = [
            'username' => 'like',
            'email' => 'like',
            'mobile' => 'like',
        ];

        $this->autoNull2EmptyString = false;
    }

    /**
     * 加密密码
     */
    protected function password(): Attribute
    {
        return new Attribute(
            set: fn ($value) => bcrypt($value),
        );
    }

    /**
     * 保存 token.
     */
    public function rememberToken(string $token): bool
    {
        $this->token = $token;

        $this->last_login_at = time();

        return $this->save();
    }

    public function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Storage::disk('uploads')->url($value),
        );
    }

    public function getJWTIdentifier()
    {
        // TODO: Implement getJWTIdentifier() method.
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        // TODO: Implement getJWTCustomClaims() method.
        return [];
    }

    /**
     * 获取订阅关系.
     */
    public function subscriptions()
    {
        return $this->hasMany(\Modules\VideoSubscription\Models\Subscription::class, 'user_id');
    }

    /**
     * 获取交易记录关系.
     */
    public function transactions()
    {
        return $this->hasMany(\Modules\Pay\Models\Transaction::class, 'user_id');
    }

    /**
     * 获取观看记录关系.
     */
    public function watchRecords()
    {
        return $this->hasMany(\Modules\VideoSubscription\Models\VideoWatchRecord::class, 'user_id');
    }
}
