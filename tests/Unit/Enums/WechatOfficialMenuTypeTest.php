<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use Modules\Wechat\Enums\WechatOfficialMenuType;
use Tests\TestCase;

class WechatOfficialMenuTypeTest extends TestCase
{
    public function test_click_type(): void
    {
        $this->assertEquals('click', WechatOfficialMenuType::CLICK->value());
        $this->assertEquals('CLICK', WechatOfficialMenuType::CLICK->name());
        $this->assertEquals('点击', WechatOfficialMenuType::CLICK->label());
    }

    public function test_view_type(): void
    {
        $this->assertEquals('view', WechatOfficialMenuType::VIEW->value());
        $this->assertEquals('VIEW', WechatOfficialMenuType::VIEW->name());
        $this->assertEquals('跳转网页', WechatOfficialMenuType::VIEW->label());
    }

    public function test_scancode_push_type(): void
    {
        $this->assertEquals('scancode_push', WechatOfficialMenuType::SCANCODE_PUSH->value());
        $this->assertEquals('扫码推事件', WechatOfficialMenuType::SCANCODE_PUSH->label());
    }

    public function test_scancode_waitmsg_type(): void
    {
        $this->assertEquals('scancode_waitmsg', WechatOfficialMenuType::SCANCODE_WAITMSG->value());
        $this->assertEquals('扫码推事件且弹出"消息接收中"提示框', WechatOfficialMenuType::SCANCODE_WAITMSG->label());
    }

    public function test_pic_sysphoto_type(): void
    {
        $this->assertEquals('pic_sysphoto', WechatOfficialMenuType::PIC_SYSPHOTO->value());
        $this->assertEquals('弹出系统拍照发图', WechatOfficialMenuType::PIC_SYSPHOTO->label());
    }

    public function test_pic_photo_or_album_type(): void
    {
        $this->assertEquals('pic_photo_or_album', WechatOfficialMenuType::PIC_PHOTO_OR_ALBUM->value());
        $this->assertEquals('弹出拍照或者相册发图', WechatOfficialMenuType::PIC_PHOTO_OR_ALBUM->label());
    }

    public function test_pic_weixin_type(): void
    {
        $this->assertEquals('pic_weixin', WechatOfficialMenuType::PIC_WEIXIN->value());
        $this->assertEquals('弹出微信相册发图', WechatOfficialMenuType::PIC_WEIXIN->label());
    }

    public function test_location_select_type(): void
    {
        $this->assertEquals('location_select', WechatOfficialMenuType::LOCATION_SELECT->value());
        $this->assertEquals('弹出地理位置选择器', WechatOfficialMenuType::LOCATION_SELECT->label());
    }

    public function test_miniprogram_type(): void
    {
        $this->assertEquals('miniprogram', WechatOfficialMenuType::MINIPROGRAM->value());
        $this->assertEquals('小程序', WechatOfficialMenuType::MINIPROGRAM->label());
    }

    public function test_media_type(): void
    {
        $this->assertEquals('media', WechatOfficialMenuType::MEDIA->value());
        $this->assertEquals('媒体素材', WechatOfficialMenuType::MEDIA->label());
    }

    public function test_options_method(): void
    {
        $options = WechatOfficialMenuType::options();

        $this->assertIsArray($options);
        $this->assertCount(10, $options);

        $firstOption = $options[0];
        $this->assertArrayHasKey('value', $firstOption);
        $this->assertArrayHasKey('label', $firstOption);
        $this->assertEquals('click', $firstOption['value']);
        $this->assertEquals('点击', $firstOption['label']);
    }

    public function test_values_method(): void
    {
        $values = WechatOfficialMenuType::values();

        $this->assertIsArray($values);
        $this->assertCount(10, $values);
        $this->assertContains('click', $values);
        $this->assertContains('view', $values);
        $this->assertContains('miniprogram', $values);
    }

    public function test_labels_method(): void
    {
        $labels = WechatOfficialMenuType::labels();

        $this->assertIsArray($labels);
        $this->assertCount(10, $labels);
        $this->assertContains('点击', $labels);
        $this->assertContains('跳转网页', $labels);
        $this->assertContains('小程序', $labels);
    }

    public function test_try_from_value(): void
    {
        $type = WechatOfficialMenuType::tryFromValue('click');

        $this->assertInstanceOf(WechatOfficialMenuType::class, $type);
        $this->assertEquals(WechatOfficialMenuType::CLICK, $type);
    }

    public function test_try_from_value_returns_null_for_invalid(): void
    {
        $type = WechatOfficialMenuType::tryFromValue('invalid');

        $this->assertNull($type);
    }

    public function test_assert_method(): void
    {
        $type = WechatOfficialMenuType::CLICK;

        $this->assertTrue($type->assert('click'));
        $this->assertFalse($type->assert('view'));
    }
}
