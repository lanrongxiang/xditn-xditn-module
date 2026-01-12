<?php

declare(strict_types=1);

namespace XditnModule\Enums;

enum Code: int implements Enum
{
    use EnumTrait;

    case SUCCESS = 10000; // 成功
    case LOST_LOGIN = 10001; //  登录失效
    case VALIDATE_FAILED = 10002; // 验证错误
    case PERMISSION_FORBIDDEN = 10003; // 权限禁止
    case LOGIN_FAILED = 10004; // 登录失败
    case FAILED = 10005; // 操作失败
    case LOGIN_EXPIRED = 10006; // 登录失效
    case LOGIN_BLACKLIST = 10007; // 黑名单
    case USER_FORBIDDEN = 10008; // 账户被禁
    case WECHAT_RESPONSE_ERROR = 40000;

    case COMPONENT_NOT_FOUND = 10010; // 组件找不到

    case WEBHOOK_FAILED = 10011; // webhook 推送失败

    case TOKEN_EXPIRED = 10012; // token 过期

    /**
     * Get message (alias for label).
     */
    public function message(): string
    {
        return $this->label();
    }

    /**
     * Get label (human readable name).
     */
    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => '操作成功',
            self::LOST_LOGIN => '身份认证失效',
            self::VALIDATE_FAILED => '验证失败',
            self::PERMISSION_FORBIDDEN => '权限禁止',
            self::LOGIN_FAILED => '登陆失败',
            self::FAILED => '操作失败',
            self::LOGIN_EXPIRED => '登陆过期',
            self::LOGIN_BLACKLIST => '已被加入黑名单',
            self::USER_FORBIDDEN => '账户被禁用',
            self::WECHAT_RESPONSE_ERROR => '微信响应错误',
            self::COMPONENT_NOT_FOUND => '组件未找到',
            self::WEBHOOK_FAILED => 'webhook推送失败',
            self::TOKEN_EXPIRED => 'token过期',
        };
    }
}
