<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use XditnModule\Base\XditnModuleModel as Model;

/**
 * @property $id
 * @property $session_id
 * @property $sender_type
 * @property $sender
 * @property $type
 * @property $content
 * @property $cost_token
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class ModelSessionMessages extends Model
{
    /** 表名 */
    protected $table = 'ai_model_session_messages';

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'session_id',
        'sender_type',
        'sender',
        'type',
        'content',
        'cost_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** 列表显示字段 */
    protected array $fields = ['id', 'session_id', 'sender_type', 'sender', 'type', 'content', 'cost_token', 'created_at'];

    /** 表单填充字段 */
    protected array $form = ['session_id', 'sender_type', 'sender', 'type', 'content', 'cost_token'];
}
