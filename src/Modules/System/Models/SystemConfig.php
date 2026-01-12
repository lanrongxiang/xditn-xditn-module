<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Modules\System\Support\Configure;
use XditnModule\Base\CatchModel as Model;
use XditnModule\Traits\DB\Cacheable;

/**
 * @property $id
 * @property $key
 * @property $value
 * @property $creator_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class SystemConfig extends Model
{
    use Cacheable;

    protected $table = 'system_config';

    protected $fillable = ['id', 'key', 'value', 'creator_id', 'created_at', 'updated_at', 'deleted_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // 设置缓存配置
        $this->cachePrefix = 'system_config';
        $this->cacheTags = ['system_config'];
        $this->cacheTTL = 0; // 永久缓存
    }

    /**
     * 保存配置.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function storeBy(array $data): mixed
    {
        foreach ($data as $k => $value) {
            $config = $this->where('key', $k)->first();
            if (!$config) {
                parent::createBy([
                    'key' => $k,
                    'value' => $value,
                ]);
            } else {
                if ($config->value != $value) {
                    $this->where('key', $k)->update([
                        'value' => $value,
                    ]);
                }
            }
        }

        (new Configure())->cache();

        return true;
    }

    /**
     * 获取配置.
     *
     * @param string $prefix
     * @param string $driver
     *
     * @return array
     */
    public static function getConfig(string $prefix, string $driver = ''): array
    {
        if ($driver) {
            $prefix = $prefix.'.'.$driver;
        }

        $config = [];
        self::query()->whereLike('key', $prefix)->get(['key', 'value'])
            ->each(function ($item) use (&$config) {
                $keys = explode('.', $item->key);
                $config[array_pop($keys)] = $item->value;
            });

        return $config;
    }

    /**
     * bootstrap to load config.
     *
     * @return mixed
     */
    public static function loaded(): mixed
    {
        $instance = new self();

        return $instance->remember('all', function () use ($instance) {
            (new Configure())->cache();

            return admin_cache_get('system_config');
        });
    }
}
