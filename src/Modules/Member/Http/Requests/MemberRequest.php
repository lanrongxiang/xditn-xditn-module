<?php

namespace Modules\Member\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    /**
     * rules.
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                Rule::unique('members')->where(function ($query) {
                    return $query->when($this->get('id'), function ($query) {
                        $query->where('id', '<>', $this->get('id'));
                    })->where('deleted_at', 0);
                }),
            ],

            'mobile' => [
                'required',
                Rule::unique('members')->where(function ($query) {
                    return $query->when($this->get('id'), function ($query) {
                        $query->where('id', '<>', $this->get('id'));
                    })->where('deleted_at', 0);
                }),
            ],
        ];
    }

    /**
     * messages.
     *
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'email.required' => '邮箱必须填写',

            'email.unique' => '邮箱已存在',

            'mobile.required' => '手机号必须填写',

            'mobile.unique' => '手机号已存在',
        ];
    }
}
