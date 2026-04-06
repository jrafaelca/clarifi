<?php

namespace App\Http\Controllers\Goals;

use App\Application\Goals\AddGoalContribution;
use App\Domain\Accounts\Models\Account;
use App\Domain\Goals\Models\Goal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Goals\StoreGoalContributionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GoalContributionController extends Controller
{
    /**
     * Store a newly created contribution.
     */
    public function store(
        StoreGoalContributionRequest $request,
        Goal $goal,
        AddGoalContribution $addGoalContribution,
    ): RedirectResponse {
        Gate::authorize('update', $goal);

        $validated = $request->validated();

        $addGoalContribution->handle($goal, [
            'account' => isset($validated['account_id']) ? Account::query()->findOrFail($validated['account_id']) : null,
            'amount' => $validated['amount'],
            'contributed_on' => $validated['contributed_on'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back();
    }
}
