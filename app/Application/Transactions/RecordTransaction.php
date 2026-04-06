<?php

namespace App\Application\Transactions;

use App\Domain\Accounts\Models\Account;
use App\Domain\Transactions\Enums\TransactionFlow;
use App\Domain\Transactions\Enums\TransactionSource;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class RecordTransaction
{
    public function __construct(
        protected RecalculateAccountBalance $recalculateAccountBalance,
    ) {}

    /**
     * Record a new income or expense transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): Transaction
    {
        /** @var Account $account */
        $account = $data['account'];
        /** @var TransactionType $type */
        $type = $data['type'];

        return DB::transaction(function () use ($team, $account, $data, $type) {
            $transaction = Transaction::create([
                'team_id' => $team->id,
                'account_id' => $account->id,
                'category_id' => $data['category']?->id,
                'type' => $type,
                'direction' => $type === TransactionType::Income ? TransactionFlow::Credit : TransactionFlow::Debit,
                'amount' => $data['amount'],
                'currency' => $account->currency,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'source' => $data['source'] ?? TransactionSource::Manual,
                'status' => $data['status'] ?? TransactionStatus::Confirmed,
                'attachment_path' => $data['attachment_path'] ?? null,
            ]);

            $this->recalculateAccountBalance->handle($account);

            return $transaction->load(['account', 'category']);
        });
    }
}
