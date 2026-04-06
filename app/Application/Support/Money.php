<?php

namespace App\Application\Support;

class Money
{
    public static function toCents(int|float|string|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round((float) $amount * 100);
    }

    public static function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
