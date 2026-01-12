<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use XditnModule\Base\CatchModel as Model;

/**
 * @property $id
 * @property $session_id
 * @property $title
 * @property $user_id
 * @property $bot_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class ModelSession extends Model
{
    /** 表名 */
    protected $table = 'ai_model_sessions';

    /** 允许填充字段 */
    protected $fillable = ['id', 'session_id', 'title', 'user_id', 'bot_id', 'created_at', 'updated_at', 'deleted_at'];

    /** 列表显示字段 */
    protected array $fields = ['id', 'session_id', 'title', 'user_id', 'bot_id', 'created_at'];

    /** 表单填充字段 */
    protected array $form = ['session_id', 'title', 'user_id', 'bot_id'];

    /** 搜索字段 */
    public array $searchable = ['title' => 'like'];
}
