<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use Tests\TestCase;
use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;
use XditnModule\Enums\Status;

class EnumTraitTest extends TestCase
{
    public function test_value_returns_enum_value(): void
    {
        $this->assertEquals(1, Status::Enable->value());
        $this->assertEquals(2, Status::Disable->value());
    }

    public function test_name_returns_enum_name(): void
    {
        $this->assertEquals('Enable', Status::Enable->name());
        $this->assertEquals('Disable', Status::Disable->name());
    }

    public function test_label_returns_human_readable_label(): void
    {
        $this->assertEquals('启用', Status::Enable->label());
        $this->assertEquals('禁用', Status::Disable->label());
    }

    public function test_options_returns_array_of_options(): void
    {
        $options = Status::options();

        $this->assertIsArray($options);
        $this->assertCount(2, $options);

        $this->assertEquals(['value' => 1, 'label' => '启用'], $options[0]);
        $this->assertEquals(['value' => 2, 'label' => '禁用'], $options[1]);
    }

    public function test_values_returns_array_of_values(): void
    {
        $values = Status::values();

        $this->assertIsArray($values);
        $this->assertCount(2, $values);
        $this->assertContains(1, $values);
        $this->assertContains(2, $values);
    }

    public function test_labels_returns_array_of_labels(): void
    {
        $labels = Status::labels();

        $this->assertIsArray($labels);
        $this->assertCount(2, $labels);
        $this->assertContains('启用', $labels);
        $this->assertContains('禁用', $labels);
    }

    public function test_try_from_value_returns_enum_for_valid_value(): void
    {
        $status = Status::tryFromValue(1);

        $this->assertInstanceOf(Status::class, $status);
        $this->assertEquals(Status::Enable, $status);
    }

    public function test_try_from_value_returns_null_for_invalid_value(): void
    {
        $status = Status::tryFromValue(999);

        $this->assertNull($status);
    }

    public function test_assert_returns_true_for_matching_value(): void
    {
        $this->assertTrue(Status::Enable->assert(1));
        $this->assertTrue(Status::Disable->assert(2));
    }

    public function test_assert_returns_false_for_non_matching_value(): void
    {
        $this->assertFalse(Status::Enable->assert(2));
        $this->assertFalse(Status::Disable->assert(1));
    }

    public function test_enum_implements_interface(): void
    {
        $this->assertInstanceOf(Enum::class, Status::Enable);
    }

    public function test_custom_enum_with_trait(): void
    {
        // 创建一个使用 EnumTrait 的枚举进行测试
        $enum = TestEnum::FIRST;

        $this->assertEquals('first', $enum->value());
        $this->assertEquals('FIRST', $enum->name());
        $this->assertEquals('第一个', $enum->label());
    }

    public function test_custom_enum_options(): void
    {
        $options = TestEnum::options();

        $this->assertCount(2, $options);
        $this->assertEquals(['value' => 'first', 'label' => '第一个'], $options[0]);
        $this->assertEquals(['value' => 'second', 'label' => '第二个'], $options[1]);
    }
}

/**
 * 测试用的枚举类.
 */
enum TestEnum: string implements Enum
{
    use EnumTrait;

    case FIRST = 'first';
    case SECOND = 'second';

    public function label(): string
    {
        return match ($this) {
            self::FIRST => '第一个',
            self::SECOND => '第二个',
        };
    }
}
