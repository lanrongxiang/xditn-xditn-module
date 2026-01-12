<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use XditnModule\Base\CatchModel as Model;

/**
 * @property $id
 * @property $user_id
 * @property $type
 * @property $title
 * @property $content
 * @property $contact
 * @property $images
 * @property $status
 * @property $reply
 * @property $replied_at
 * @property $replied_by
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 */
class Feedback extends Model
{
    protected $table = 'cms_feedbacks';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'content',
        'contact',
        'images',
        'status',
        'reply',
        'replied_at',
        'replied_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'images' => 'array',
        'replied_at' => 'integer',
    ];

    public array $searchable = [
        'type' => '=',
        'status' => '=',
        'user_id' => '=',
        'title' => 'like',
    ];

    /**
     * 反馈类型映射.
     */
    protected function typeText(): Attribute
    {
        return new Attribute(
            get: fn () => match ($this->type) {
                'bug' => __('feedback.type.bug'),
                'suggestion' => __('feedback.type.suggestion'),
                'other' => __('feedback.type.other'),
                default => $this->type,
            }
        );
    }

    /**
     * 状态文本映射.
     */
    protected function statusText(): Attribute
    {
        return new Attribute(
            get: fn () => match ($this->status) {
                'pending' => __('feedback.status.pending'),
                'processing' => __('feedback.status.processing'),
                'resolved' => __('feedback.status.resolved'),
                'closed' => __('feedback.status.closed'),
                default => $this->status,
            }
        );
    }

    /**
     * 关联用户.
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Member\Models\Members::class, 'user_id');
    }

    /**
     * 关联回复人.
     */
    public function replier()
    {
        return $this->belongsTo(\Modules\User\Models\Users::class, 'replied_by');
    }
}
