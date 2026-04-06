<?php

namespace Database\Factories;

use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Category>
     */
    protected $model = Category::class;

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
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(CategoryType::cases()),
            'parent_id' => null,
            'icon' => 'wallet',
            'color' => '#0f766e',
            'is_system' => false,
        ];
    }

    /**
     * Associate the category to the given workspace.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'is_system' => false,
        ]);
    }

    /**
     * Indicate that the category is a system category.
     */
    public function system(): static
    {
        return $this->state(fn () => [
            'team_id' => null,
            'is_system' => true,
        ]);
    }
}
