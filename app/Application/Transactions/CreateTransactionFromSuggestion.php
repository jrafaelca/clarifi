<?php

namespace App\Application\Transactions;

use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\Transactions\Enums\TransactionSource;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Team;

class CreateTransactionFromSuggestion
{
    public function __construct(
        protected RecordTransaction $recordTransaction,
    ) {}

    /**
     * Materialize a transaction suggestion into the ledger.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        Team $team,
        array $payload,
        Account $account,
        ?Category $category = null,
    ): Transaction {
        return $this->recordTransaction->handle($team, [
            'account' => $account,
            'category' => $category,
            'type' => TransactionType::from((string) $payload['type']),
            'amount' => $payload['amount'],
            'transaction_date' => $payload['transaction_date'],
            'description' => $payload['description'],
            'notes' => $payload['notes'] ?? null,
            'status' => isset($payload['status'])
                ? TransactionStatus::from((string) $payload['status'])
                : TransactionStatus::Confirmed,
            'source' => TransactionSource::Import,
            'attachment_path' => $payload['attachment_path'] ?? null,
        ]);
    }
}
