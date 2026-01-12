<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use XditnModule\Base\CatchModel as Model;

/**
 * @property $id
 * @property $knowledge_id
 * @property $filename
 * @property $extension
 * @property $content
 * @property $embedding_content
 * @property $creator_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class KnowledgeFiles extends Model
{
    /** 表名 */
    protected $table = 'ai_knowledge_files';

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'knowledge_id',
        'filename',
        'extension',
        'content',
        'embedding_content',
        'creator_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** 列表显示字段 */
    protected array $fields = ['id', 'knowledge_id', 'content', 'embedding_content', 'created_at', 'updated_at'];

    /** 表单填充字段 */
    protected array $form = ['knowledge_id', 'filename', 'extension', 'content', 'embedding_content'];
}
