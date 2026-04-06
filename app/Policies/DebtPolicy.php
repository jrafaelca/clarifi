<?php

namespace App\Policies;

use App\Domain\Debts\Models\Debt;
use App\Models\User;

class DebtPolicy
{
    /**
     * Determine whether the user can view any debts.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the debt.
     */
    public function view(User $user, Debt $debt): bool
    {
        return $user->currentTeam?->is($debt->team) ?? false;
    }

    /**
     * Determine whether the user can create debts.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can update the debt.
     */
    public function update(User $user, Debt $debt): bool
    {
        return $user->currentTeam?->is($debt->team) ?? false;
    }
}
