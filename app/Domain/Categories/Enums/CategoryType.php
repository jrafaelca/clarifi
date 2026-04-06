<?php

namespace App\Domain\Categories\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    /**
     * Get the label for the category type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Income => 'Income',
            self::Expense => 'Expense',
        };
    }

    /**
     * Get the available values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type) => $type->value,
            self::cases(),
        );
    }
}
