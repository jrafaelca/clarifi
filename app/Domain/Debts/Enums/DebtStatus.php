<?php

namespace App\Domain\Debts\Enums;

enum DebtStatus: string
{
    case Active = 'active';
    case Paid = 'paid';
    case Archived = 'archived';
}
