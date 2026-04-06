<?php

namespace App\Application\Categories;

use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Models\Team;
use Illuminate\Support\Str;

class CreateCategory
{
    /**
     * Create or reuse a category for the given workspace.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data, bool $matchExisting = false): Category
    {
        $type = $data['type'] instanceof CategoryType
            ? $data['type']
            : CategoryType::from((string) $data['type']);

        if ($matchExisting) {
            $existing = $this->findExisting($team, (string) $data['name'], $type);

            if ($existing !== null) {
                return $existing;
            }
        }

        return Category::create([
            'team_id' => $team->id,
            'name' => $data['name'],
            'type' => $type,
            'parent_id' => $data['parent_id'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'is_system' => false,
        ]);
    }

    protected function findExisting(Team $team, string $name, CategoryType $type): ?Category
    {
        return Category::query()
            ->where(function ($query) use ($team) {
                $query->whereNull('team_id')
                    ->orWhere('team_id', $team->id);
            })
            ->where('type', $type)
            ->get()
            ->first(fn (Category $category) => $this->normalize($category->name) === $this->normalize($name));
    }

    protected function normalize(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();
    }
}
