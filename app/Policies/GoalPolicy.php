<?php

namespace App\Policies;

use App\Domain\Goals\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    /**
     * Determine whether the user can view any goals.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the goal.
     */
    public function view(User $user, Goal $goal): bool
    {
        return $user->currentTeam?->is($goal->team) ?? false;
    }

    /**
     * Determine whether the user can create goals.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can update the goal.
     */
    public function update(User $user, Goal $goal): bool
    {
        return $user->currentTeam?->is($goal->team) ?? false;
    }
}
