<?php

namespace Database\Factories;

use App\Domain\Accounts\Models\Account;
use App\Domain\Debts\Models\Debt;
use App\Domain\Debts\Models\DebtPayment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebtPayment>
 */
class DebtPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DebtPayment>
     */
    protected $model = DebtPayment::class;

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
            'debt_id' => Debt::factory()->for($team, 'team'),
            'account_id' => Account::factory()->for($team, 'team'),
            'transaction_id' => null,
            'amount' => fake()->randomFloat(2, 25, 500),
            'paid_on' => now(),
            'notes' => null,
        ];
    }
}
