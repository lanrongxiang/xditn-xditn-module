<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use Tests\TestCase;
use XditnModule\Enums\Code;

class CodeTest extends TestCase
{
    public function test_success_code(): void
    {
        $this->assertEquals(10000, Code::SUCCESS->value());
        $this->assertEquals('SUCCESS', Code::SUCCESS->name());
        $this->assertEquals('操作成功', Code::SUCCESS->label());
    }

    public function test_lost_login_code(): void
    {
        $this->assertEquals(10001, Code::LOST_LOGIN->value());
        $this->assertEquals('LOST_LOGIN', Code::LOST_LOGIN->name());
        $this->assertEquals('身份认证失效', Code::LOST_LOGIN->label());
    }

    public function test_validate_failed_code(): void
    {
        $this->assertEquals(10002, Code::VALIDATE_FAILED->value());
        $this->assertEquals('VALIDATE_FAILED', Code::VALIDATE_FAILED->name());
        $this->assertEquals('验证失败', Code::VALIDATE_FAILED->label());
    }

    public function test_permission_forbidden_code(): void
    {
        $this->assertEquals(10003, Code::PERMISSION_FORBIDDEN->value());
        $this->assertEquals('PERMISSION_FORBIDDEN', Code::PERMISSION_FORBIDDEN->name());
        $this->assertEquals('权限禁止', Code::PERMISSION_FORBIDDEN->label());
    }

    public function test_failed_code(): void
    {
        $this->assertEquals(10005, Code::FAILED->value());
        $this->assertEquals('FAILED', Code::FAILED->name());
        $this->assertEquals('操作失败', Code::FAILED->label());
    }

    public function test_message_method(): void
    {
        $this->assertEquals('操作成功', Code::SUCCESS->message());
        $this->assertEquals('身份认证失效', Code::LOST_LOGIN->message());
    }

    public function test_options_method(): void
    {
        $options = Code::options();
        $this->assertIsArray($options);
        $this->assertNotEmpty($options);

        $firstOption = $options[0];
        $this->assertArrayHasKey('value', $firstOption);
        $this->assertArrayHasKey('label', $firstOption);
    }

    public function test_values_method(): void
    {
        $values = Code::values();
        $this->assertIsArray($values);
        $this->assertContains(10000, $values);
        $this->assertContains(10001, $values);
    }
}
