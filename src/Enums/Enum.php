<?php

declare(strict_types=1);

namespace XditnModule\Enums;

/**
 * Enum Interface.
 *
 * 所有枚举类都应该实现此接口
 */
interface Enum
{
    /**
     * Get the enum value.
     */
    public function value(): int|string;

    /**
     * Get the enum name.
     */
    public function name(): string;

    /**
     * Get the human-readable label.
     */
    public function label(): string;
}
