<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use XditnModule\Base\CatchModel as Model;

/**
 * @property $id
 * @property $logo
 * @property $title
 * @property $desc
 * @property $prompt
 * @property $is_use_knowledge
 * @property $contexts
 * @property $max_tokens
 * @property $temperature
 * @property $top_p
 * @property $status
 * @property $creator_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class ChatBots extends Model
{
    /** 表名 */
    protected $table = 'ai_chat_bots';

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'logo',
        'title',
        'desc',
        'prompt',
        'is_use_knowledge',
        'contexts',
        'max_tokens',
        'temperature',
        'top_p',
        'status',
        'creator_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** 列表显示字段 */
    protected array $fields = ['id', 'logo', 'title', 'desc', 'prompt', 'is_use_knowledge', 'contexts', 'max_tokens', 'temperature', 'top_p', 'status', 'creator_id', 'created_at', 'updated_at'];

    /** 表单填充字段 */
    protected array $form = ['logo', 'title', 'desc', 'prompt', 'is_use_knowledge', 'contexts', 'max_tokens', 'temperature', 'top_p', 'status'];

    /** 表单关联关系 */
    protected array $formRelations = ['models'];

    /**
     * 智能体关联模型.
     *
     * @return BelongsToMany
     */
    public function models(): BelongsToMany
    {
        return $this->belongsToMany(AiModels::class, 'ai_robot_models', 'robot_id', 'model_id');
    }

}
