<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use XditnModule\Base\CatchModel as Model;

/**
 * @property $id
 * @property $title
 * @property $logo
 * @property $provider
 * @property $api_url
 * @property $api_key
 * @property $version
 * @property $status
 * @property $creator_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class AiProviders extends Model
{
    /** 表名 */
    protected $table = 'ai_providers';

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'title',
        'logo',
        'provider',
        'api_url',
        'api_key',
        'version',
        'status',
        'creator_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** 列表显示字段 */
    protected array $fields = ['id', 'title', 'logo', 'provider', 'api_url', 'status', 'api_key', 'version', 'created_at'];

    /** 表单填充字段 */
    protected array $form = ['title', 'logo', 'provider', 'api_url', 'api_key', 'status', 'version'];

    /**
     * 模型.
     *
     * @return HasMany
     */
    public function models(): HasMany
    {
        return $this->hasMany(AiModels::class, 'provider_id', 'id');
    }    /**
     * @param $id
     *
     * @return AiProviders|null
     */
    public static function findProvider($id): ?AiProviders
    {
        return AiProviders::query()->where('id', $id)->first(['api_key', 'api_url as url', 'provider']);
    }
}
