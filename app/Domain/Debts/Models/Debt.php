<?php

namespace App\Domain\Debts\Models;

use App\Domain\Debts\Enums\DebtStatus;
use App\Domain\Shared\Concerns\BelongsToWorkspace;
use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'name',
    'lender',
    'currency',
    'original_amount',
    'current_balance',
    'interest_rate',
    'minimum_payment',
    'due_date',
    'status',
    'notes',
])]
class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DebtFactory
    {
        return DebtFactory::new();
    }

    /**
     * Get the payments for the debt.
     *
     * @return HasMany<DebtPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'minimum_payment' => 'decimal:2',
            'due_date' => 'immutable_date',
            'status' => DebtStatus::class,
        ];
    }
}
