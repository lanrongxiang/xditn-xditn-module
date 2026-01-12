<?php

declare(strict_types=1);

namespace Modules\Mail\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use XditnModule\Base\XditnModuleModel as Model;

/**
 * @property int $id
 * @property int $task_id 发送任务Id
 * @property string $recipient 收件人
 * @property int $is_delivered 是否送达
 * @property int $is_opened 是否打开
 * @property int $is_clicked 是否点击
 * @property int $is_bounced 是否退回
 * @property string $opened_ip 打开时的IP地址
 * @property string $clicked_ip 点击时的IP地址
 * @property string $clicked_url 被点击的链接
 * @property \Carbon\Carbon $delivered_at 送达时间
 * @property \Carbon\Carbon $opened_at 打开时间
 * @property \Carbon\Carbon $clicked_at 点击时间
 * @property \Carbon\Carbon $bounced_at 退回时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $deleted_at 软删除
 */
class MailTrackingLog extends Model
{
    /** 表名 */
    protected $table = 'mail_tracking_log';

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'task_id',
        'recipient',
        'is_delivered',
        'is_opened',
        'is_clicked',
        'is_bounced',
        'opened_ip',
        'clicked_ip',
        'clicked_url',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** casts */
    protected $casts = [
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    /** 列表显示字段 */
    protected array $fields = [
        'id',
        'task_id',
        'recipient',
        'is_delivered',
        'is_opened',
        'is_clicked',
        'is_bounced',
        'opened_ip',
        'clicked_ip',
        'clicked_url',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'created_at',
    ];

    /** 表单填充字段 */
    protected array $form = [
        'task_id',
        'recipient',
        'is_delivered',
        'is_opened',
        'is_clicked',
        'is_bounced',
        'opened_ip',
        'clicked_ip',
        'clicked_url',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
    ];

    /** 搜索字段 */
    public array $searchable = [
        'recipient' => 'like',
        'task_id' => '=',
        'opened_ip' => 'like',
        'clicked_ip' => 'like',
        'clicked_url' => 'like',
    ];

    /** 追加字段 */
    protected $appends = [
        'is_delivered_text',
        'is_opened_text',
        'is_clicked_text',
        'is_bounced_text',
    ];

    /**
     * is_delivered 字段转换器.
     *
     * @return Attribute
     */
    public function isDeliveredText(): Attribute
    {
        $text = [0 => '未送达', 1 => '已送达'];

        return Attribute::make(get: fn ($value) => $text[$this->is_delivered] ?? '未知');
    }

    /**
     * is_opened 字段转换器.
     *
     * @return Attribute
     */
    public function isOpenedText(): Attribute
    {
        $text = [0 => '未打开', 1 => '已打开'];

        return Attribute::make(get: fn ($value) => $text[$this->is_opened] ?? '未知');
    }

    /**
     * is_clicked 字段转换器.
     *
     * @return Attribute
     */
    public function isClickedText(): Attribute
    {
        $text = [0 => '未点击', 1 => '已点击'];

        return Attribute::make(get: fn ($value) => $text[$this->is_clicked] ?? '未知');
    }

    /**
     * is_bounced 字段转换器.
     *
     * @return Attribute
     */
    public function isBouncedText(): Attribute
    {
        $text = [0 => '未退回', 1 => '已退回'];

        return Attribute::make(get: fn ($value) => $text[$this->is_bounced] ?? '未知');
    }
}
