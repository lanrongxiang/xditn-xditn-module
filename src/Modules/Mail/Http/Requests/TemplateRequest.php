<?php

declare(strict_types=1);

namespace Modules\Mail\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 验证表单.
 *
 * @class TemplateRequest
 */
class TemplateRequest extends FormRequest
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
        return ['name' => 'required', 'content' => 'required'];
    }

    /**
     * 验证规则信息.
     *
     * @return array
     */
    public function messages(): array
    {
        return ['name.required' => '模板名称必填', 'content.required' => '模板内容必填'];
    }
}
