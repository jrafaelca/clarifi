<?php

namespace App\Domain\Accounts\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Savings = 'savings';
    case CreditCard = 'credit_card';

    /**
     * Get the label for the account type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Bank => 'Banco',
            self::Savings => 'Ahorros',
            self::CreditCard => 'Tarjeta de credito',
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
