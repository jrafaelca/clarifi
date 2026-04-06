<?php

namespace Database\Factories\Ai;

use App\Models\Ai\IngestionSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngestionSuggestion>
 */
class IngestionSuggestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => IngestionBatchFactory::new(),
            'suggestion_key' => 'transaction-1',
            'kind' => 'transaction',
            'status' => 'draft',
            'confidence' => 0.92,
            'source_excerpt' => fake()->sentence(),
            'payload_json' => [
                'description' => fake()->sentence(3),
                'transaction_date' => now()->toDateString(),
                'amount' => '12500.00',
                'type' => 'expense',
            ],
            'materialized_model_type' => null,
            'materialized_model_id' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }
}
