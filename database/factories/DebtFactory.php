<?php

namespace Database\Factories;

use App\Domain\Debts\Enums\DebtStatus;
use App\Domain\Debts\Models\Debt;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Debt>
     */
    protected $model = Debt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalAmount = fake()->randomFloat(2, 500, 10000);
        $currentBalance = fake()->randomFloat(2, 50, $originalAmount);
        $team = Team::factory()->personal();

        return [
            'team_id' => $team,
            'name' => fake()->randomElement(['Student loan', 'Car loan', 'Personal loan', 'Store card']),
            'lender' => fake()->company(),
            'currency' => config('clarifi.default_currency'),
            'original_amount' => $originalAmount,
            'current_balance' => $currentBalance,
            'interest_rate' => fake()->randomFloat(2, 0, 35),
            'minimum_payment' => fake()->randomFloat(2, 25, 500),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'status' => $currentBalance > 0 ? DebtStatus::Active : DebtStatus::Paid,
            'notes' => null,
        ];
    }

    /**
     * Associate the debt to the given workspace.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'currency' => $team->currency,
        ]);
    }
}
