<?php

namespace Modules\Wechat;

use Modules\Wechat\Providers\WechatServiceProvider;
use XditnModule\Support\Module\Installer as ModuleInstaller;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '微信管理',
            'name' => 'wechat',
            'path' => 'wechat',
            'keywords' => '微信管理, wechat',
            'description' => '微信管理模块',
            'provider' => WechatServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
        // 微信模块不需要额外的 composer 包
    }

    protected function removePackages(): void
    {
        // 微信模块不需要移除 composer 包
    }
}
