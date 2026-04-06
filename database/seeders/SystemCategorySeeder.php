<?php

namespace Database\Seeders;

use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use Illuminate\Database\Seeder;

class SystemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Salary', 'type' => CategoryType::Income, 'icon' => 'briefcase', 'color' => '#15803d'],
            ['name' => 'Freelance', 'type' => CategoryType::Income, 'icon' => 'sparkles', 'color' => '#0f766e'],
            ['name' => 'Other income', 'type' => CategoryType::Income, 'icon' => 'wallet', 'color' => '#0369a1'],
            ['name' => 'Groceries', 'type' => CategoryType::Expense, 'icon' => 'shopping-basket', 'color' => '#ea580c'],
            ['name' => 'Housing', 'type' => CategoryType::Expense, 'icon' => 'house', 'color' => '#dc2626'],
            ['name' => 'Transport', 'type' => CategoryType::Expense, 'icon' => 'bus', 'color' => '#2563eb'],
            ['name' => 'Utilities', 'type' => CategoryType::Expense, 'icon' => 'zap', 'color' => '#7c3aed'],
            ['name' => 'Dining', 'type' => CategoryType::Expense, 'icon' => 'utensils', 'color' => '#db2777'],
            ['name' => 'Health', 'type' => CategoryType::Expense, 'icon' => 'heart-pulse', 'color' => '#be123c'],
            ['name' => 'Education', 'type' => CategoryType::Expense, 'icon' => 'graduation-cap', 'color' => '#1d4ed8'],
            ['name' => 'Debt payments', 'type' => CategoryType::Expense, 'icon' => 'badge-dollar-sign', 'color' => '#7c2d12'],
            ['name' => 'Other expense', 'type' => CategoryType::Expense, 'icon' => 'folder', 'color' => '#475569'],
        ])->each(function (array $category): void {
            Category::query()->firstOrCreate(
                [
                    'team_id' => null,
                    'name' => $category['name'],
                    'type' => $category['type'],
                ],
                [
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'is_system' => true,
                ],
            );
        });
    }
}
