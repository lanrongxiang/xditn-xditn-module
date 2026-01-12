<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use XditnModule\Base\XditnModuleModel as Model;

/**
 * @property $id
 * @property $title
 * @property $description
 * @property $embedding_model
 * @property $sort
 * @property $status
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class KnowledgeBase extends Model
{
    /** 表名 */
    protected $table = 'ai_knowledge_bases';

    /** 允许填充字段 */
    protected $fillable = ['id', 'title', 'description', 'embedding_model', 'sort', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /** 列表显示字段 */
    protected array $fields = ['id', 'title',  'embedding_model', 'sort', 'status', 'created_at', 'updated_at'];

    /** 表单填充字段 */
    protected array $form = ['title', 'description', 'embedding_model', 'sort', 'status'];

    /** 搜索字段 */
    public array $searchable = ['title' => 'like'];
}
