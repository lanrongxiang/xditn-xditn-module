<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Tests\TestCase;
use XditnModule\Support\ApiVersion;

class ApiVersionTest extends TestCase
{
    public function test_normalize_numeric_version(): void
    {
        $this->assertEquals('v1', ApiVersion::normalize('1'));
        $this->assertEquals('v2', ApiVersion::normalize('2'));
        $this->assertEquals('v10', ApiVersion::normalize('10'));
    }

    public function test_normalize_string_version(): void
    {
        $this->assertEquals('v1', ApiVersion::normalize('v1'));
        $this->assertEquals('v2', ApiVersion::normalize('v2'));
    }

    public function test_normalize_trims_whitespace(): void
    {
        $this->assertEquals('v1', ApiVersion::normalize(' v1 '));
        $this->assertEquals('v1', ApiVersion::normalize(' 1 '));
    }

    public function test_extract_number(): void
    {
        $this->assertEquals(1, ApiVersion::extractNumber('v1'));
        $this->assertEquals(2, ApiVersion::extractNumber('v2'));
        $this->assertEquals(10, ApiVersion::extractNumber('v10'));
        $this->assertEquals(1, ApiVersion::extractNumber('1'));
        $this->assertEquals(2, ApiVersion::extractNumber('2'));
    }

    public function test_extract_number_default(): void
    {
        $this->assertEquals(1, ApiVersion::extractNumber('invalid'));
        $this->assertEquals(1, ApiVersion::extractNumber(''));
    }

    public function test_default_version(): void
    {
        $default = ApiVersion::default();

        $this->assertIsString($default);
        $this->assertMatchesRegularExpression('/^v\d+$/', $default);
    }

    public function test_supported_versions(): void
    {
        $supported = ApiVersion::supported();

        $this->assertIsArray($supported);
        $this->assertNotEmpty($supported);
        $this->assertContains('v1', $supported);
    }

    public function test_is_supported(): void
    {
        $this->assertTrue(ApiVersion::isSupported('v1'));
        $this->assertTrue(ApiVersion::isSupported('1')); // 会被规范化
        $this->assertFalse(ApiVersion::isSupported('v999'));
    }

    public function test_current_returns_default_when_not_set(): void
    {
        $current = ApiVersion::current();

        $this->assertIsString($current);
        $this->assertEquals(ApiVersion::default(), $current);
    }

    public function test_current_number_returns_int(): void
    {
        $number = ApiVersion::currentNumber();

        $this->assertIsInt($number);
        $this->assertGreaterThanOrEqual(1, $number);
    }

    public function test_match_returns_correct_handler_result(): void
    {
        // 由于 current() 返回默认版本 v1，所以应该匹配 v1 处理器
        $result = ApiVersion::match([
            'v1' => fn () => 'v1_result',
            'v2' => fn () => 'v2_result',
        ]);

        $this->assertEquals('v1_result', $result);
    }

    public function test_match_returns_default_when_no_match(): void
    {
        $result = ApiVersion::match(
            [
                'v999' => fn () => 'v999_result',
            ],
            fn () => 'default_result'
        );

        $this->assertEquals('default_result', $result);
    }

    public function test_match_returns_null_when_no_match_and_no_default(): void
    {
        $result = ApiVersion::match([
            'v999' => fn () => 'v999_result',
        ]);

        $this->assertNull($result);
    }
}
