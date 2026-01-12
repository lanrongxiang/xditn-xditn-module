<?php

declare(strict_types=1);

namespace Modules\Mail\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Mail\Enums\SendTaskStatus;
use XditnModule\Base\XditnModuleModel as Model;

/**
 * @property int $id
 * @property string $from_address 发件人邮箱
 * @property string $subject 邮件主题
 * @property int $template_id 模板ID
 * @property string $remark 备注
 * @property int $send_at 发送时间:0=立即发送,1=发送时间
 * @property string $recipients 收件人邮箱
 * @property int|null $recipients_num
 * @property int|null $success_num
 * @property int|null $failure_num
 * @property SendTaskStatus|null $status
 * @property int $is_tracking
 * @property int|null $finished_at
 * @property int $creator_id 创建人ID
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $deleted_at 软删除
 */
class SendTask extends Model
{
    /** 表名 */
    protected $table = 'mail_send_tasks';

    /** casts */
    protected $casts = [
        'status' => SendTaskStatus::class,
        'finished_at' => 'datetime:Y-m-d H:i:s',
    ];

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'from_address',
        'subject',
        'template_id',
        'remark',
        'send_at',
        'recipients',
        'recipients_num',
        'success_num',
        'failure_num',
        'status',
        'is_tracking',
        'finished_at',
        'creator_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** 列表显示字段 */
    protected array $fields = [
        'id',
        'from_address',
        'subject',
        'template_id',
        'send_at',
        'recipients_num',
        'success_num',
        'failure_num',
        'status',
        'is_tracking',
        'created_at',
        'finished_at',
    ];

    /** 表单填充字段 */
    protected array $form = ['from_address', 'is_tracking', 'subject', 'template_id', 'remark', 'send_at', 'recipients'];

    /** 搜索字段 */
    public array $searchable = ['from_address' => 'like'];

    /**
     * 关联发送日志.
     */
    public function sendTaskLogs(): HasMany
    {
        return $this->hasMany(MailTrackingLog::class, 'task_id', 'id');
    }

    /**
     * send_at 字段转换器.
     *
     * @return Attribute
     */
    public function sendAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value > 0 ? date('Y-m-d H:i:s', $value) : '立即发送'
        );
    }

    /**
     * @return Attribute
     */
    public function recipients(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }

    /**
     * 检查任务是否可以执行.
     */
    public function canExecute(): bool
    {
        return $this->status === SendTaskStatus::PENDING;
    }

    /**
     * 检查任务是否已完成.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [SendTaskStatus::COMPLETED, SendTaskStatus::FAILED]);
    }

    /**
     * 检查任务是否正在处理.
     */
    public function isProcessing(): bool
    {
        return $this->status === SendTaskStatus::PROCESSING;
    }

    /**
     * @return bool
     */
    public function isTracking(): bool
    {
        return (bool) $this->is_tracking;
    }
}
