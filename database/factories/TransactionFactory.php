<?php

namespace Database\Factories;

use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\Transactions\Enums\TransactionFlow;
use App\Domain\Transactions\Enums\TransactionSource;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Transaction>
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $team = Team::factory()->personal();
        $account = Account::factory()->for($team, 'team');
        $category = Category::factory()->for($team, 'team');

        return [
            'team_id' => $team,
            'account_id' => $account,
            'related_account_id' => null,
            'category_id' => $category,
            'type' => TransactionType::Expense,
            'direction' => TransactionFlow::Debit,
            'amount' => fake()->randomFloat(2, 5, 500),
            'currency' => config('clarifi.default_currency'),
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'description' => fake()->sentence(3),
            'notes' => null,
            'source' => TransactionSource::Manual,
            'status' => TransactionStatus::Confirmed,
            'attachment_path' => null,
            'transfer_group_uuid' => null,
        ];
    }

    /**
     * Associate the transaction to the given workspace.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'currency' => $team->currency,
        ]);
    }
}
