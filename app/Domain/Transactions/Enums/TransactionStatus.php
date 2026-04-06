<?php

namespace App\Domain\Transactions\Enums;

enum TransactionStatus: string
{
    case Confirmed = 'confirmed';
    case Pending = 'pending';

    /**
     * Get the label for the transaction status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmada',
            self::Pending => 'Pendiente',
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
            static fn (self $status) => $status->value,
            self::cases(),
        );
    }
}
