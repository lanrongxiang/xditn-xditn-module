<?php

declare(strict_types=1);

namespace XditnModule\Enums;

/**
 * Enum Trait.
 *
 * 为枚举类提供通用方法
 */
trait EnumTrait
{
    /**
     * Get the enum value (for Backed Enums).
     */
    public function value(): int|string
    {
        return $this->value;
    }

    /**
     * Get the enum name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Assert if the enum value equals the given value.
     */
    public function assert(mixed $value): bool
    {
        return $this->value() == $value;
    }

    /**
     * Get all enum cases as options array.
     *
     * @return array<int, array{value: int|string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value(), 'label' => $case->label()],
            self::cases()
        );
    }

    /**
     * Get all enum values.
     *
     * @return array<int, int|string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value(), self::cases());
    }

    /**
     * Get all enum labels.
     *
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return array_map(fn (self $case) => $case->label(), self::cases());
    }

    /**
     * Try to create enum from value.
     */
    public static function tryFromValue(int|string $value): ?static
    {
        return self::tryFrom($value);
    }
}
