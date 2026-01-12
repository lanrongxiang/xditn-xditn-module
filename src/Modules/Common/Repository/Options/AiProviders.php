<?php

namespace Modules\Common\Repository\Options;

class AiProviders extends Option implements OptionInterface
{
    /**
     * 预定义的 AI 服务商列表.
     */
    public function get(): array
    {
        return [
            [
                'label' => 'OpenAI',
                'value' => 'openai',
            ],
            [
                'label' => 'Anthropic',
                'value' => 'anthropic',
            ],
            [
                'label' => 'Google Gemini',
                'value' => 'google',
            ],
            [
                'label' => '阿里云通义千问',
                'value' => 'dashscope',
            ],
            [
                'label' => '腾讯云混元',
                'value' => 'tencent',
            ],
            [
                'label' => '百度文心一言',
                'value' => 'baidu',
            ],
            [
                'label' => 'Moonshot',
                'value' => 'moonshot',
            ],
            [
                'label' => 'DeepSeek',
                'value' => 'deepseek',
            ],
            [
                'label' => '智谱AI',
                'value' => 'zhipu',
            ],
            [
                'label' => '其他',
                'value' => 'other',
            ],
        ];
    }
}
