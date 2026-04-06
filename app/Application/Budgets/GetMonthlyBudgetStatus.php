<?php

namespace App\Application\Budgets;

use App\Application\Support\Money;
use App\Domain\Budgets\Models\Budget;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Team;
use Carbon\CarbonImmutable;

class GetMonthlyBudgetStatus
{
    /**
     * Get the budget status for the given month.
     *
     * @return array<string, mixed>
     */
    public function handle(Team $team, CarbonImmutable $month): array
    {
        $monthStart = $month->startOfMonth();
        $monthEnd = $month->endOfMonth();

        $budgets = Budget::query()
            ->forTeam($team)
            ->with('category')
            ->whereDate('month', $monthStart->toDateString())
            ->orderBy('category_id')
            ->get();

        $spentByCategory = Transaction::query()
            ->forTeam($team)
            ->where('type', TransactionType::Expense)
            ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $budgeted = 0;
        $spent = 0;

        $items = $budgets->map(function (Budget $budget) use (&$budgeted, &$spent, $spentByCategory) {
            $budgetAmount = Money::toCents($budget->amount);
            $spentAmount = Money::toCents($spentByCategory->get($budget->category_id, 0));

            $budgeted += $budgetAmount;
            $spent += $spentAmount;

            return [
                'id' => $budget->id,
                'category' => [
                    'id' => $budget->category->id,
                    'name' => $budget->category->name,
                ],
                'amount' => Money::fromCents($budgetAmount),
                'spent' => Money::fromCents($spentAmount),
                'remaining' => Money::fromCents($budgetAmount - $spentAmount),
                'isOverBudget' => $spentAmount > $budgetAmount,
                'month' => $budget->month->format('Y-m'),
            ];
        })->values();

        return [
            'month' => $monthStart->format('Y-m'),
            'currency' => $team->currency,
            'totals' => [
                'budgeted' => Money::fromCents($budgeted),
                'spent' => Money::fromCents($spent),
                'remaining' => Money::fromCents($budgeted - $spent),
            ],
            'items' => $items,
        ];
    }
}
