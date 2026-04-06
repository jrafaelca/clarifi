<?php

namespace App\Domain\Transactions\Enums;

enum TransactionFlow: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    /**
     * Get the label for the transaction flow.
     */
    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
        };
    }
}
