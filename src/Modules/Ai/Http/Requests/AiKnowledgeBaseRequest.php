<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 验证表单.
 *
 * @class AiKnowledgeBaseRequest
 */
class AiKnowledgeBaseRequest extends FormRequest
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
        return [
            'title' => 'required',
        ];
    }

    /**
     * 验证规则信息.
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }
}
