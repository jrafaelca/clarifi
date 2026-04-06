<?php

namespace App\Http\Controllers\Accounts;

use App\Application\Transactions\RecalculateAccountBalance;
use App\Domain\Accounts\Enums\AccountType;
use App\Domain\Accounts\Models\Account;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\SaveAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display the accounts page.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        return Inertia::render('accounts/Index', [
            'workspace' => [
                'currency' => $team->currency,
                'name' => $team->name,
            ],
            'accounts' => Account::query()
                ->forTeam($team)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (Account $account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'typeLabel' => $account->type->label(),
                    'currency' => $account->currency,
                    'initialBalance' => $account->initial_balance,
                    'currentBalance' => $account->current_balance,
                    'institution' => $account->institution,
                    'isActive' => $account->is_active,
                    'updatedAt' => $account->updated_at?->toIso8601String(),
                ])
                ->values(),
            'accountTypes' => collect(AccountType::cases())
                ->map(fn (AccountType $type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(SaveAccountRequest $request, RecalculateAccountBalance $recalculateAccountBalance): RedirectResponse
    {
        Gate::authorize('create', Account::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();

        $account = Account::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'currency' => $team->currency,
            'initial_balance' => $validated['initial_balance'],
            'current_balance' => $validated['initial_balance'],
            'institution' => $validated['institution'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $recalculateAccountBalance->handle($account);

        return back();
    }

    /**
     * Update the specified account.
     */
    public function update(SaveAccountRequest $request, Account $account, RecalculateAccountBalance $recalculateAccountBalance): RedirectResponse
    {
        Gate::authorize('update', $account);

        $validated = $request->validated();

        $account->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'initial_balance' => $validated['initial_balance'],
            'institution' => $validated['institution'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $recalculateAccountBalance->handle($account);

        return back();
    }

    /**
     * Archive the specified account.
     */
    public function archive(Request $request, Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->update(['is_active' => false]);

        return back();
    }

    /**
     * Restore the specified account.
     */
    public function restore(Request $request, Account $account): RedirectResponse
    {
        Gate::authorize('update', $account);

        $account->update(['is_active' => true]);

        return back();
    }
}
