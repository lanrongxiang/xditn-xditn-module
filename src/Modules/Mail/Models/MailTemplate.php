<?php

declare(strict_types=1);

namespace Modules\Mail\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use XditnModule\Base\CatchModel as Model;
use XditnModule\Enums\Status;

/**
 * @property int $id
 * @property string $name 模板名称
 * @property string $code 模板code
 * @property string $mode
 * @property string $content 模板内容
 * @property int $status 状态:1=启用,2=禁用
 * @property int $creator_id 创建人ID
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $deleted_at 软删除
 */
class MailTemplate extends Model
{
    /** 表名 */
    protected $table = 'mail_templates';

    /** 允许填充字段 */
    protected $fillable = [
        'id',
        'name',
        'code',
        'mode',
        'content',
        'status',
        'creator_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** casts */
    protected $casts = [];

    /** 列表显示字段 */
    protected array $fields = ['id', 'name', 'code', 'status', 'created_at'];

    /** 表单填充字段 */
    protected array $form = ['name', 'code', 'content', 'status', 'mode'];

    /** 搜索字段 */
    public array $searchable = ['name' => 'like'];

    /** 追加字段 */
    protected $appends = ['status_text'];

    /**
     * status 字段转换器.
     *
     * @return Attribute
     */
    public function statusText(): Attribute
    {
        $text = [2 => '禁用', 1 => '启用'];

        return Attribute::make(get: fn ($value) => $text[$this->status] ?? '');
    }    /**
     * is html mode.
     *
     * @return bool
     */
    public function isHtml(): bool
    {
        return strtolower($this->mode) === 'html';
    }

    /**
     * is blade mode.
     *
     * @return bool
     */
    public function isBlade(): bool
    {
        return strtolower($this->mode) === 'blade';
    }

    /**
     * 模板是否禁用.
     *
     * @return bool
     */
    protected function isDisabled(): bool
    {
        return Status::Disable->assert($this->status);
    }
}
