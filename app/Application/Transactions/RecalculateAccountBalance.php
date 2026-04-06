<?php

namespace App\Application\Transactions;

use App\Application\Support\Money;
use App\Domain\Accounts\Models\Account;
use App\Domain\Transactions\Enums\TransactionFlow;
use App\Domain\Transactions\Enums\TransactionStatus;

class RecalculateAccountBalance
{
    /**
     * Recalculate the current balance for the given account.
     */
    public function handle(Account $account): Account
    {
        $initialBalance = Money::toCents($account->initial_balance);

        $delta = $account->transactions()
            ->where('status', TransactionStatus::Confirmed)
            ->get(['amount', 'direction'])
            ->reduce(
                fn (int $carry, $transaction) => $carry + (
                    $transaction->direction === TransactionFlow::Credit
                        ? Money::toCents($transaction->amount)
                        : Money::toCents($transaction->amount) * -1
                ),
                0,
            );

        $account->forceFill([
            'current_balance' => Money::fromCents($initialBalance + $delta),
        ])->save();

        return $account->refresh();
    }
}
