<?php

namespace Database\Factories\Ai;

use App\Models\Ai\IngestionFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IngestionFile>
 */
class IngestionFileFactory extends Factory
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
            'disk' => 'local',
            'path' => 'ai-ingestion/test/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'original_name' => 'statement.pdf',
            'size_bytes' => fake()->numberBetween(512, 8192),
        ];
    }
}
