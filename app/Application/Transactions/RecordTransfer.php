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
use Illuminate\Support\Str;

class RecordTransfer
{
    public function __construct(
        protected RecalculateAccountBalance $recalculateAccountBalance,
    ) {}

    /**
     * Record a new transfer between two accounts.
     *
     * @param  array<string, mixed>  $data
     * @return array{outgoing: Transaction, incoming: Transaction}
     */
    public function handle(Team $team, array $data): array
    {
        /** @var Account $sourceAccount */
        $sourceAccount = $data['source_account'];
        /** @var Account $destinationAccount */
        $destinationAccount = $data['destination_account'];

        return DB::transaction(function () use ($team, $sourceAccount, $destinationAccount, $data) {
            $transferGroup = (string) Str::uuid();

            $outgoing = Transaction::create([
                'team_id' => $team->id,
                'account_id' => $sourceAccount->id,
                'related_account_id' => $destinationAccount->id,
                'type' => TransactionType::Transfer,
                'direction' => TransactionFlow::Debit,
                'amount' => $data['amount'],
                'currency' => $sourceAccount->currency,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'source' => $data['source'] ?? TransactionSource::Manual,
                'status' => $data['status'] ?? TransactionStatus::Confirmed,
                'attachment_path' => $data['attachment_path'] ?? null,
                'transfer_group_uuid' => $transferGroup,
            ]);

            $incoming = Transaction::create([
                'team_id' => $team->id,
                'account_id' => $destinationAccount->id,
                'related_account_id' => $sourceAccount->id,
                'type' => TransactionType::Transfer,
                'direction' => TransactionFlow::Credit,
                'amount' => $data['amount'],
                'currency' => $destinationAccount->currency,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'source' => $data['source'] ?? TransactionSource::Manual,
                'status' => $data['status'] ?? TransactionStatus::Confirmed,
                'attachment_path' => $data['attachment_path'] ?? null,
                'transfer_group_uuid' => $transferGroup,
            ]);

            $this->recalculateAccountBalance->handle($sourceAccount);
            $this->recalculateAccountBalance->handle($destinationAccount);

            return [
                'outgoing' => $outgoing->load(['account', 'relatedAccount']),
                'incoming' => $incoming->load(['account', 'relatedAccount']),
            ];
        });
    }
}
