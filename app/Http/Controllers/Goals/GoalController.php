<?php

namespace App\Http\Controllers\Goals;

use App\Domain\Accounts\Models\Account;
use App\Domain\Goals\Models\Goal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Goals\SaveGoalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    /**
     * Display the goals page.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        return Inertia::render('goals/Index', [
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
            'goals' => Goal::query()
                ->forTeam($team)
                ->with(['contributions.account'])
                ->orderBy('name')
                ->get()
                ->map(fn (Goal $goal) => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'targetAmount' => $goal->target_amount,
                    'currentAmount' => $goal->current_amount,
                    'currency' => $goal->currency,
                    'targetDate' => $goal->target_date?->toDateString(),
                    'notes' => $goal->notes,
                    'status' => $goal->status->value,
                    'contributions' => $goal->contributions
                        ->sortByDesc('contributed_on')
                        ->values()
                        ->map(fn ($contribution) => [
                            'id' => $contribution->id,
                            'amount' => $contribution->amount,
                            'contributedOn' => $contribution->contributed_on->toDateString(),
                            'accountName' => $contribution->account?->name,
                            'notes' => $contribution->notes,
                        ]),
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created goal.
     */
    public function store(SaveGoalRequest $request): RedirectResponse
    {
        Gate::authorize('create', Goal::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();

        Goal::create([
            'team_id' => $team->id,
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? 0,
            'currency' => $team->currency,
            'target_date' => $validated['target_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);

        return back();
    }

    /**
     * Update the specified goal.
     */
    public function update(SaveGoalRequest $request, Goal $goal): RedirectResponse
    {
        Gate::authorize('update', $goal);

        $validated = $request->validated();

        $goal->update([
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? $goal->current_amount,
            'target_date' => $validated['target_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back();
    }
}
