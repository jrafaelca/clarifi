<?php

namespace App\Domain\Transactions\Models;

use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\Shared\Concerns\BelongsToWorkspace;
use App\Domain\Transactions\Enums\TransactionFlow;
use App\Domain\Transactions\Enums\TransactionSource;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'account_id',
    'related_account_id',
    'category_id',
    'type',
    'direction',
    'amount',
    'currency',
    'transaction_date',
    'description',
    'notes',
    'source',
    'status',
    'attachment_path',
    'transfer_group_uuid',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    /**
     * Get the account that owns the transaction.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the related account for transfers.
     *
     * @return BelongsTo<Account, $this>
     */
    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'related_account_id');
    }

    /**
     * Get the category for the transaction.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'direction' => TransactionFlow::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'immutable_date',
            'source' => TransactionSource::class,
            'status' => TransactionStatus::class,
        ];
    }
}
