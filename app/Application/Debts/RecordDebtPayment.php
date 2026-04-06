<?php

namespace App\Application\Debts;

use App\Application\Support\Money;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Debts\Enums\DebtStatus;
use App\Domain\Debts\Models\Debt;
use App\Domain\Debts\Models\DebtPayment;
use App\Domain\Transactions\Enums\TransactionType;
use Illuminate\Support\Facades\DB;

class RecordDebtPayment
{
    public function __construct(
        protected RecordTransaction $recordTransaction,
    ) {}

    /**
     * Record a payment against the given debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Debt $debt, array $data): DebtPayment
    {
        return DB::transaction(function () use ($debt, $data) {
            $transaction = null;

            if ($data['account'] !== null) {
                $transaction = $this->recordTransaction->handle($debt->team, [
                    'account' => $data['account'],
                    'category' => $data['category'] ?? null,
                    'type' => TransactionType::Expense,
                    'amount' => $data['amount'],
                    'transaction_date' => $data['paid_on'],
                    'description' => $data['description'] ?? 'Debt payment: '.$debt->name,
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $payment = $debt->payments()->create([
                'team_id' => $debt->team_id,
                'account_id' => $data['account']?->id,
                'transaction_id' => $transaction?->id,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            $remainingBalance = max(
                0,
                Money::toCents($debt->current_balance) - Money::toCents($payment->amount),
            );

            $debt->forceFill([
                'current_balance' => Money::fromCents($remainingBalance),
                'status' => $remainingBalance === 0 ? DebtStatus::Paid : DebtStatus::Active,
            ])->save();

            return $payment->load(['account', 'transaction']);
        });
    }
}
