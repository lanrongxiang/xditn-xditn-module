<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Support\Sms\SmsCode;
use Modules\User\Http\Requests\SmsCodeRequest;
use Modules\User\Services\Auth as AuthService;
use XditnModule\Base\XditnModuleController as Controller;
use XditnModule\Exceptions\FailedException;
use XditnModule\Facade\Admin;

/**
 * @group 管理端
 *
 *  后台用户认证
 *
 * @subgroup 用户认证
 *
 * @subgroupDescription  后台用户认证
 */
class AuthController extends Controller
{
    /**
     * 登录.
     *
     * @bodyParam account string 账号
     * @bodyParam password string 密码
     * @bodyParam remember boolean 记住我
     * @bodyParam mobile string 手机号
     * @bodyParam sms_code string 短信验证码
     * @bodyParam wx_code string 微信code
     *
     * @responseField token string 用户授权 token
     *
     * @unauthenticated
     */
    public function login(Request $request, AuthService $auth): array
    {
        return $auth->attempt($request->all());
    }

    /**
     * 退出登录.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data array 空数组
     *
     * @return array
     */
    public function logout(): array
    {
        Admin::logout();

        return [];
    }

    /**
     * 登录短信验证码
     *
     * @bodyParam mobile string required 手机号
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data string 验证码（仅用于测试环境）
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function loginSmsCode(SmsCodeRequest $request, SmsCode $smsCode): array
    {
        return [$smsCode->login($request->get('mobile'))];
    }

    /**
     * 微信登录配置.
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 微信配置
     * @responseField data.app_id string 微信 AppID
     * @responseField data.callback string 回调地址
     *
     * @unauthenticated
     *
     * @return array|\Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    public function wechat()
    {
        $wechatPcConfig = config('wechat.pc');
        if (empty($wechatPcConfig)) {
            throw new FailedException('请先通过密码登录方式登录，在系统管理中配置微信相关配置');
        }

        unset($wechatPcConfig['app_secret']);
        $wechatPcConfig['callback'] = urlencode($wechatPcConfig['callback']);

        return $wechatPcConfig;
    }

    /**
     * 图形验证码
     *
     * @responseField code int 状态码
     * @responseField message string 提示信息
     * @responseField data object 验证码信息
     * @responseField data.key string 验证码 key
     * @responseField data.image string 验证码图片（base64）
     *
     * @unauthenticated
     *
     * @return mixed
     */
    public function captcha()
    {
        return app('captcha')->create(api: true);
    }
}
