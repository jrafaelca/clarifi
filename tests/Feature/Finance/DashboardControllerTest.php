<?php

use App\Domain\Accounts\Models\Account;
use App\Domain\Budgets\Models\Budget;
use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Domain\Debts\Models\Debt;
use App\Domain\Goals\Models\Goal;
use App\Domain\Transactions\Enums\TransactionFlow;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shows the current workspace financial summary', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $account = Account::factory()->forTeam($team)->create([
        'initial_balance' => 500,
        'current_balance' => 1700,
    ]);

    $incomeCategory = Category::factory()->forTeam($team)->create([
        'name' => 'Salary',
        'type' => CategoryType::Income,
    ]);

    $expenseCategory = Category::factory()->forTeam($team)->create([
        'name' => 'Food',
        'type' => CategoryType::Expense,
    ]);

    Transaction::factory()->forTeam($team)->create([
        'account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'type' => TransactionType::Income,
        'direction' => TransactionFlow::Credit,
        'status' => TransactionStatus::Confirmed,
        'amount' => 1500,
        'transaction_date' => now()->startOfMonth()->addDay(),
        'description' => 'Monthly salary',
    ]);

    Transaction::factory()->forTeam($team)->create([
        'account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'type' => TransactionType::Expense,
        'direction' => TransactionFlow::Debit,
        'status' => TransactionStatus::Confirmed,
        'amount' => 300,
        'transaction_date' => now()->startOfMonth()->addDays(2),
        'description' => 'Groceries',
    ]);

    Budget::factory()->forTeam($team)->create([
        'category_id' => $expenseCategory->id,
        'month' => now()->startOfMonth(),
        'amount' => 500,
    ]);

    Goal::factory()->forTeam($team)->create([
        'name' => 'Emergency fund',
        'target_amount' => 1000,
        'current_amount' => 250,
    ]);

    Debt::factory()->forTeam($team)->create([
        'name' => 'Card balance',
        'current_balance' => 600,
        'minimum_payment' => 75,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', [
        'current_team' => $team->slug,
    ]));

    $response
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('workspace.currency', $team->currency)
            ->where('metrics.totalBalance', '1700.00')
            ->where('metrics.incomeThisMonth', '1500.00')
            ->where('metrics.expensesThisMonth', '300.00')
            ->where('metrics.cashFlowThisMonth', '1200.00')
            ->where('metrics.budgetRemaining', '200.00')
            ->where('metrics.goalProgress', '250.00')
            ->where('metrics.goalTarget', '1000.00')
            ->where('metrics.debtBalance', '600.00')
            ->has('latestTransactions', 2)
        );
});
