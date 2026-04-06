<?php

namespace Database\Factories;

use App\Domain\Accounts\Models\Account;
use App\Domain\Goals\Models\Goal;
use App\Domain\Goals\Models\GoalContribution;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalContribution>
 */
class GoalContributionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<GoalContribution>
     */
    protected $model = GoalContribution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $team = Team::factory()->personal();

        return [
            'team_id' => $team,
            'goal_id' => Goal::factory()->for($team, 'team'),
            'account_id' => Account::factory()->for($team, 'team'),
            'amount' => fake()->randomFloat(2, 25, 500),
            'contributed_on' => now(),
            'notes' => null,
        ];
    }
}
