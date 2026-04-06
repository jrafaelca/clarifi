<?php

namespace App\Domain\Transactions\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';

    /**
     * Get the label for the transaction type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Gasto',
            self::Transfer => 'Transferencia',
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
