<?php

namespace Modules\Wechat\Providers;

use XditnModule\Providers\XditnModuleModuleServiceProvider;

class WechatServiceProvider extends XditnModuleModuleServiceProvider
{
    /**
     * 模块名称.
     */
    public function moduleName(): string
    {
        return 'wechat';
    }
}
