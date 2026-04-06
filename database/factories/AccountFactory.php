<?php

namespace Database\Factories;

use App\Domain\Accounts\Enums\AccountType;
use App\Domain\Accounts\Models\Account;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Account>
     */
    protected $model = Account::class;

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
            'name' => fake()->company().' '.fake()->randomElement(['Checking', 'Savings', 'Wallet']),
            'type' => fake()->randomElement(AccountType::cases()),
            'currency' => config('clarifi.default_currency'),
            'initial_balance' => fake()->randomFloat(2, 0, 5000),
            'current_balance' => fake()->randomFloat(2, 0, 5000),
            'institution' => fake()->company(),
            'is_active' => true,
        ];
    }

    /**
     * Associate the account to the given workspace.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'currency' => $team->currency,
        ]);
    }
}
