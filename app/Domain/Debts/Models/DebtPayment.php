<?php

namespace App\Domain\Debts\Models;

use App\Domain\Accounts\Models\Account;
use App\Domain\Shared\Concerns\BelongsToWorkspace;
use App\Domain\Transactions\Models\Transaction;
use Database\Factories\DebtPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'debt_id',
    'account_id',
    'transaction_id',
    'amount',
    'paid_on',
    'notes',
])]
class DebtPayment extends Model
{
    /** @use HasFactory<DebtPaymentFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DebtPaymentFactory
    {
        return DebtPaymentFactory::new();
    }

    /**
     * Get the debt that owns the payment.
     *
     * @return BelongsTo<Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * Get the source account for the payment.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the related financial transaction.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'immutable_date',
        ];
    }
}
