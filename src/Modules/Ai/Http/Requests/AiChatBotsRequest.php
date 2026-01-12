<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 验证表单.
 *
 * @class AiChatBotsRequest
 */
class AiChatBotsRequest extends FormRequest
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
            'logo' => 'required',
            'title' => 'required',
            'desc' => 'required',
            'prompt' => 'required',
            'models' => 'required',
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
