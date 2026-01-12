<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cms\Models\Option;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 内容设置
 *
 * @subgroupDescription  后台内容管理->内容设置
 */
class SettingController extends Controller
{
    /**
     * 获取设置.
     *
     * @queryParam key string 设置名称
     *
     * @responseField data object 设置内容
     * @responseField data.is_simple_url int 是否开启简短链接:1 开启 2 关闭
     * @responseField data.site_comment_avatar string 评论的头像: 默认identicon
     * @responseField data.site_comment_avatar_proxy string 评论头像代理，默认https://gravatar.loli.net
     * @responseField data.site_comment_check boolean 是否开启评论审核:1 开启 2 关闭
     * @responseField data.site_comment_limit string 是否开启评论验证码:1 开启 2 关闭
     * @responseField data.site_comment_need_email string 评论是否需要邮箱
     * @responseField data.site_comment_order_desc string 评论倒叙配列
     * @responseField data.site_comment_per_page string 评论每页显示数量
     * @responseField data.site_date_format string 站点日期格式
     * @responseField data.site_logo string 站点 logo 图
     * @responseField data.site_time_format string 站点时间格式
     * @responseField data.site_title string 站点标题
     * @responseField data.site_url_struct string 是否开启评论举报:1 开启 2 关闭
     *
     * @return array|int|mixed|string
     */
    public function index($key = '*')
    {
        return Option::getValues($key);
    }

    /**
     * 保存配置.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function store(Request $request)
    {
        $optionKeys = Option::pluck('key');

        foreach ($request->all() as $key => $value) {
            // 如果是多语言内容（JSON格式），需要转换为JSON字符串
            if (is_array($value) && in_array($key, ['site_privacy_policy', 'site_user_agreement', 'site_subscription_terms', 'site_name', 'site_description'])) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if ($optionKeys->contains($key)) {
                Option::where('key', $key)->update([
                    'value' => $value,
                    'creator_id' => $this->getLoginUserId(),
                ]);
            } else {
                app(Option::class)->storeBy([
                    'key' => $key,
                    'value' => $value,
                    'creator_id' => $this->getLoginUserId(),
                ]);
            }
        }
    }

    /**
     * 获取网站基本配置（管理端）.
     *
     * @responseField data object 网站配置
     * @responseField data.site_name object|string 网站名称（多语言对象或字符串）
     * @responseField data.site_logo string 网站Logo URL
     * @responseField data.site_icon string 网站图标 URL
     * @responseField data.site_description object|string 网站描述（多语言对象或字符串）
     *
     * @return mixed
     */
    public function siteConfig()
    {
        $keys = ['site_name', 'site_logo', 'site_icon', 'site_description'];
        $values = Option::getValues($keys);

        $result = [];
        foreach ($keys as $key) {
            $value = $values[$key] ?? null;
            // 如果是JSON字符串，解析为对象
            if (is_string($value) && str_starts_with($value, '{')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $result[$key] = $decoded;
                } else {
                    $result[$key] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 获取隐私政策（管理端）.
     *
     * @responseField data object|string 隐私政策内容（多语言对象或字符串）
     *
     * @return mixed
     */
    public function privacyPolicy()
    {
        $value = Option::getValues('site_privacy_policy');
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * 获取用户协议（管理端）.
     *
     * @responseField data object|string 用户协议内容（多语言对象或字符串）
     *
     * @return mixed
     */
    public function userAgreement()
    {
        $value = Option::getValues('site_user_agreement');
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * 获取订阅条款（管理端）.
     *
     * @responseField data object|string 订阅条款内容（多语言对象或字符串）
     *
     * @return mixed
     */
    public function subscriptionTerms()
    {
        $value = Option::getValues('site_subscription_terms');
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }
}
