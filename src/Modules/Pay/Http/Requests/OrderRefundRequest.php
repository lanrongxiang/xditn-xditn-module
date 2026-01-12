<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRefundRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function rules(): array
    {
        return [
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'refund_amount.required' => '退款金额必填',
            'refund_amount.numeric' => '退款金额必须是数字',
            'refund_amount.min' => '退款金额不能小于0.01',
            'reason.max' => '退款原因不能超过500个字符',
        ];
    }

    /**
     * 获取退款金额（元）.
     */
    public function getRefundAmount(): float
    {
        return (float) $this->input('refund_amount');
    }

    /**
     * 获取退款原因.
     */
    public function getReason(): ?string
    {
        return $this->input('reason');
    }
}
