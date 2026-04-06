<?php

namespace App\Application\Dashboard;

use App\Application\Budgets\GetMonthlyBudgetStatus;
use App\Application\Support\Money;
use App\Domain\Accounts\Models\Account;
use App\Domain\Debts\Models\Debt;
use App\Domain\Goals\Models\Goal;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Team;
use Carbon\CarbonImmutable;

class GetDashboardSummary
{
    public function __construct(
        protected GetMonthlyBudgetStatus $getMonthlyBudgetStatus,
    ) {}

    /**
     * Build the dashboard summary for the given workspace.
     *
     * @return array<string, mixed>
     */
    public function handle(Team $team, ?CarbonImmutable $month = null): array
    {
        $month ??= CarbonImmutable::now();

        $monthStart = $month->startOfMonth();
        $monthEnd = $month->endOfMonth();

        $accounts = Account::query()
            ->forTeam($team)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $income = Transaction::query()
            ->forTeam($team)
            ->where('type', TransactionType::Income)
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $expenses = Transaction::query()
            ->forTeam($team)
            ->where('type', TransactionType::Expense)
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $goals = Goal::query()
            ->forTeam($team)
            ->orderByDesc('updated_at')
            ->get();

        $debts = Debt::query()
            ->forTeam($team)
            ->orderBy('due_date')
            ->get();

        $latestTransactions = Transaction::query()
            ->forTeam($team)
            ->with(['account', 'category', 'relatedAccount'])
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        $budgetStatus = $this->getMonthlyBudgetStatus->handle($team, $month);

        $totalBalance = Money::fromCents(
            $accounts->reduce(
                fn (int $carry, Account $account) => $carry + Money::toCents($account->current_balance),
                0,
            ),
        );

        $goalTarget = $goals->reduce(
            fn (int $carry, Goal $goal) => $carry + Money::toCents($goal->target_amount),
            0,
        );

        $goalCurrent = $goals->reduce(
            fn (int $carry, Goal $goal) => $carry + Money::toCents($goal->current_amount),
            0,
        );

        $debtBalance = $debts->reduce(
            fn (int $carry, Debt $debt) => $carry + Money::toCents($debt->current_balance),
            0,
        );

        return [
            'workspace' => [
                'name' => $team->name,
                'currency' => $team->currency,
                'month' => $monthStart->format('F Y'),
            ],
            'metrics' => [
                'totalBalance' => $totalBalance,
                'incomeThisMonth' => Money::fromCents(Money::toCents($income)),
                'expensesThisMonth' => Money::fromCents(Money::toCents($expenses)),
                'cashFlowThisMonth' => Money::fromCents(
                    Money::toCents($income) - Money::toCents($expenses),
                ),
                'budgetRemaining' => $budgetStatus['totals']['remaining'],
                'goalProgress' => Money::fromCents($goalCurrent),
                'goalTarget' => Money::fromCents($goalTarget),
                'debtBalance' => Money::fromCents($debtBalance),
            ],
            'accounts' => $accounts->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'typeLabel' => $account->type->label(),
                'currentBalance' => $account->current_balance,
                'isActive' => $account->is_active,
            ])->values(),
            'budget' => $budgetStatus,
            'goals' => $goals->map(fn (Goal $goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'currentAmount' => $goal->current_amount,
                'targetAmount' => $goal->target_amount,
                'status' => $goal->status->value,
            ])->values(),
            'debts' => $debts->map(fn (Debt $debt) => [
                'id' => $debt->id,
                'name' => $debt->name,
                'currentBalance' => $debt->current_balance,
                'minimumPayment' => $debt->minimum_payment,
                'status' => $debt->status->value,
                'dueDate' => $debt->due_date?->toDateString(),
            ])->values(),
            'latestTransactions' => $latestTransactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'amount' => $transaction->amount,
                'type' => $transaction->type->value,
                'direction' => $transaction->direction->value,
                'transactionDate' => $transaction->transaction_date->toDateString(),
                'accountName' => $transaction->account->name,
                'categoryName' => $transaction->category?->name,
                'relatedAccountName' => $transaction->relatedAccount?->name,
            ])->values(),
        ];
    }
}
