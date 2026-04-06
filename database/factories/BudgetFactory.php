<?php

namespace Database\Factories;

use App\Domain\Budgets\Models\Budget;
use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Budget>
     */
    protected $model = Budget::class;

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
            'category_id' => Category::factory()->for($team, 'team')->state([
                'type' => CategoryType::Expense,
            ]),
            'month' => now()->startOfMonth(),
            'currency' => config('clarifi.default_currency'),
            'amount' => fake()->randomFloat(2, 50, 2000),
        ];
    }

    /**
     * Associate the budget to the given workspace.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'currency' => $team->currency,
        ]);
    }
}
