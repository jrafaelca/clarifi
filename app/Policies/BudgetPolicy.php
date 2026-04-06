<?php

namespace App\Policies;

use App\Domain\Budgets\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * Determine whether the user can view any budgets.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the budget.
     */
    public function view(User $user, Budget $budget): bool
    {
        return $user->currentTeam?->is($budget->team) ?? false;
    }

    /**
     * Determine whether the user can create budgets.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can update the budget.
     */
    public function update(User $user, Budget $budget): bool
    {
        return $user->currentTeam?->is($budget->team) ?? false;
    }
}
