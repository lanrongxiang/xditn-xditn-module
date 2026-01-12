<?php

declare(strict_types=1);

namespace Modules\Mail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 验证表单.
 *
 * @class SendTaskRequest
 */
class SendTaskRequest extends FormRequest
{
    /** 验证错误立即停止 */
    protected $stopOnFirstFailure = true;

    /**
     * 验证规则.
     *
     * @return array
     */
    public function rules(): array
    {
        return ['from_address' => 'required', 'subject' => 'required', 'template_id' => 'required', 'send_at' => 'required'];
    }

    /**
     * 验证规则信息.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'from_address.required' => '发件人邮箱必填',
            'subject.required' => '邮件主题必填',
            'template_id.required' => '模板ID必填',
            'send_at.required' => '发送时间必填',
        ];
    }
}
