<?php

namespace App\Domain\Debts\Enums;

enum DebtStatus: string
{
    case Active = 'active';
    case Paid = 'paid';
    case Archived = 'archived';

    /**
     * Get the label for the debt status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Paid => 'Pagada',
            self::Archived => 'Archivada',
        };
    }
}
