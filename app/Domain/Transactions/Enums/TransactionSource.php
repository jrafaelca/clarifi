<?php

namespace App\Domain\Transactions\Enums;

enum TransactionSource: string
{
    case Manual = 'manual';
    case Ai = 'ai';
    case Import = 'import';
}
