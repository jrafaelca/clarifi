<?php

namespace App\Policies;

use App\Domain\Accounts\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Determine whether the user can view any accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the account.
     */
    public function view(User $user, Account $account): bool
    {
        return $user->currentTeam?->is($account->team) ?? false;
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can update the account.
     */
    public function update(User $user, Account $account): bool
    {
        return $user->currentTeam?->is($account->team) ?? false;
    }
}
