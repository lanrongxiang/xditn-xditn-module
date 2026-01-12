<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRefundAgreeRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'is_agree' => 'required|boolean',
            'refuse_reason' => 'required_if:is_agree,false|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'is_agree.required' => '是否同意必填',
            'is_agree.boolean' => '是否同意必须是布尔值',
            'refuse_reason.required_if' => '拒绝原因必填',
            'refuse_reason.max' => '拒绝原因不能超过500个字符',
        ];
    }

    /**
     * 是否同意.
     */
    public function isAgree(): bool
    {
        return (bool) $this->input('is_agree');
    }

    /**
     * 获取拒绝原因.
     */
    public function getRefuseReason(): ?string
    {
        return $this->input('refuse_reason');
    }
}
