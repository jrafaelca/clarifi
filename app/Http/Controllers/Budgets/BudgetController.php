<?php

namespace App\Http\Controllers\Budgets;

use App\Application\Budgets\GetMonthlyBudgetStatus;
use App\Domain\Budgets\Models\Budget;
use App\Domain\Categories\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgets\SaveBudgetRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    /**
     * Display the budgets page.
     */
    public function index(Request $request, GetMonthlyBudgetStatus $getMonthlyBudgetStatus): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();
        $month = CarbonImmutable::createFromFormat(
            'Y-m',
            $request->string('month')->toString() ?: now()->format('Y-m'),
        ) ?: CarbonImmutable::now();

        return Inertia::render('budgets/Index', [
            'workspace' => [
                'currency' => $team->currency,
                'name' => $team->name,
            ],
            'month' => $month->format('Y-m'),
            'status' => $getMonthlyBudgetStatus->handle($team, $month),
            'categories' => Category::query()
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
                    'isSystem' => $category->is_system,
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created budget.
     */
    public function store(SaveBudgetRequest $request): RedirectResponse
    {
        Gate::authorize('create', Budget::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();

        Budget::updateOrCreate(
            [
                'team_id' => $team->id,
                'category_id' => $validated['category_id'],
                'month' => CarbonImmutable::createFromFormat('Y-m', $validated['month'])->startOfMonth()->toDateString(),
            ],
            [
                'currency' => $team->currency,
                'amount' => $validated['amount'],
            ],
        );

        return back();
    }

    /**
     * Update the specified budget.
     */
    public function update(SaveBudgetRequest $request, Budget $budget): RedirectResponse
    {
        Gate::authorize('update', $budget);

        $validated = $request->validated();

        $budget->update([
            'category_id' => $validated['category_id'],
            'month' => CarbonImmutable::createFromFormat('Y-m', $validated['month'])->startOfMonth()->toDateString(),
            'amount' => $validated['amount'],
        ]);

        return back();
    }
}
