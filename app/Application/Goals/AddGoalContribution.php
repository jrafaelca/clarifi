<?php

namespace App\Application\Goals;

use App\Application\Support\Money;
use App\Domain\Goals\Enums\GoalStatus;
use App\Domain\Goals\Models\Goal;
use App\Domain\Goals\Models\GoalContribution;
use Illuminate\Support\Facades\DB;

class AddGoalContribution
{
    /**
     * Add a contribution to the given goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Goal $goal, array $data): GoalContribution
    {
        return DB::transaction(function () use ($goal, $data) {
            $contribution = $goal->contributions()->create([
                'team_id' => $goal->team_id,
                'account_id' => $data['account']?->id,
                'amount' => $data['amount'],
                'contributed_on' => $data['contributed_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            $currentAmount = Money::toCents($goal->current_amount);
            $newAmount = $currentAmount + Money::toCents($contribution->amount);

            $goal->forceFill([
                'current_amount' => Money::fromCents($newAmount),
                'status' => $newAmount >= Money::toCents($goal->target_amount)
                    ? GoalStatus::Completed
                    : GoalStatus::Active,
            ])->save();

            return $contribution->load('account');
        });
    }
}
