<?php

namespace Database\Factories;

use App\Domain\Goals\Enums\GoalStatus;
use App\Domain\Goals\Models\Goal;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Goal>
     */
    protected $model = Goal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $targetAmount = fake()->randomFloat(2, 500, 5000);
        $currentAmount = fake()->randomFloat(2, 0, $targetAmount);
        $team = Team::factory()->personal();

        return [
            'team_id' => $team,
            'name' => fake()->randomElement(['Emergency fund', 'Travel', 'Home office', 'Car maintenance']),
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'currency' => config('clarifi.default_currency'),
            'target_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'notes' => null,
            'status' => $currentAmount >= $targetAmount ? GoalStatus::Completed : GoalStatus::Active,
        ];
    }

    /**
     * Associate the goal to the given workspace.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'currency' => $team->currency,
        ]);
    }
}
