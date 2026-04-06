<?php

namespace App\Application\Settings;

use App\Domain\Accounts\Models\Account;
use App\Domain\Budgets\Models\Budget;
use App\Domain\Debts\Models\Debt;
use App\Domain\Goals\Models\Goal;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UpdateWorkspaceCurrency
{
    /**
     * Sync the workspace currency across the MVP financial records.
     */
    public function handle(Team $team, string $currency): void
    {
        DB::transaction(function () use ($team, $currency) {
            $team->forceFill([
                'currency' => $currency,
            ])->save();

            Account::query()->forTeam($team)->update(['currency' => $currency]);
            Transaction::query()->forTeam($team)->update(['currency' => $currency]);
            Budget::query()->forTeam($team)->update(['currency' => $currency]);
            Goal::query()->forTeam($team)->update(['currency' => $currency]);
            Debt::query()->forTeam($team)->update(['currency' => $currency]);
        });
    }
}
