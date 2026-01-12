<?php

declare(strict_types=1);

namespace Modules\System\Models;

use XditnModule\Base\XditnModuleModel as Model;

/**
 * @property $id
 * @property $path
 * @property $average_time_taken
 * @property $count
 * @property $fail_count
 * @property $success_count
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class ConnectorLogStatistics extends Model
{
    protected $table = 'system_connector_log_statistics';

    protected $fillable = [
        'id', 'path', 'average_time_taken', 'count', 'fail_count', 'success_count', 'created_at', 'updated_at', 'deleted_at',
    ];
}
