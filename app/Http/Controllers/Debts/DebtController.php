<?php

namespace App\Http\Controllers\Debts;

use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\Debts\Models\Debt;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debts\SaveDebtRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    /**
     * Display the debts page.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        return Inertia::render('debts/Index', [
            'workspace' => [
                'currency' => $team->currency,
                'name' => $team->name,
            ],
            'accounts' => Account::query()
                ->forTeam($team)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Account $account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                ])
                ->values(),
            'expenseCategories' => Category::query()
                ->where(function ($query) use ($team) {
                    $query->whereNull('team_id')
                        ->orWhere('team_id', $team->id);
                })
                ->where('type', 'expense')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values(),
            'debts' => Debt::query()
                ->forTeam($team)
                ->with(['payments.account'])
                ->orderBy('due_date')
                ->orderBy('name')
                ->get()
                ->map(fn (Debt $debt) => [
                    'id' => $debt->id,
                    'name' => $debt->name,
                    'lender' => $debt->lender,
                    'currency' => $debt->currency,
                    'originalAmount' => $debt->original_amount,
                    'currentBalance' => $debt->current_balance,
                    'interestRate' => $debt->interest_rate,
                    'minimumPayment' => $debt->minimum_payment,
                    'dueDate' => $debt->due_date?->toDateString(),
                    'status' => $debt->status->label(),
                    'notes' => $debt->notes,
                    'payments' => $debt->payments
                        ->sortByDesc('paid_on')
                        ->values()
                        ->map(fn ($payment) => [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'paidOn' => $payment->paid_on->toDateString(),
                            'accountName' => $payment->account?->name,
                            'notes' => $payment->notes,
                        ]),
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created debt.
     */
    public function store(SaveDebtRequest $request): RedirectResponse
    {
        Gate::authorize('create', Debt::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();

        Debt::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
            'lender' => $validated['lender'] ?? null,
            'currency' => $team->currency,
            'original_amount' => $validated['original_amount'],
            'current_balance' => $validated['current_balance'] ?? $validated['original_amount'],
            'interest_rate' => $validated['interest_rate'] ?? 0,
            'minimum_payment' => $validated['minimum_payment'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back();
    }

    /**
     * Update the specified debt.
     */
    public function update(SaveDebtRequest $request, Debt $debt): RedirectResponse
    {
        Gate::authorize('update', $debt);

        $validated = $request->validated();

        $debt->update([
            'name' => $validated['name'],
            'lender' => $validated['lender'] ?? null,
            'original_amount' => $validated['original_amount'],
            'current_balance' => $validated['current_balance'] ?? $debt->current_balance,
            'interest_rate' => $validated['interest_rate'] ?? 0,
            'minimum_payment' => $validated['minimum_payment'],
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back();
    }
}
