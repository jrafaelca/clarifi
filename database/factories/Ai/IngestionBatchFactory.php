<?php

namespace Database\Factories\Ai;

use App\Models\Ai\IngestionBatch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngestionBatch>
 */
class IngestionBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory()->personal(),
            'user_id' => User::factory(),
            'conversation_id' => fake()->uuid(),
            'source_kind' => 'text',
            'status' => 'draft',
            'raw_prompt' => fake()->sentence(),
            'summary' => fake()->sentence(),
            'error_message' => null,
            'processed_at' => now(),
        ];
    }
}
