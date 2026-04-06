<?php

namespace App\Http\Controllers\Categories;

use App\Application\Categories\CreateCategory;
use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\SaveCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display the categories page.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        return Inertia::render('categories/Index', [
            'workspace' => [
                'currency' => $team->currency,
                'name' => $team->name,
            ],
            'categories' => Category::query()
                ->where(function ($query) use ($team) {
                    $query->whereNull('team_id')
                        ->orWhere('team_id', $team->id);
                })
                ->with('parent')
                ->orderByDesc('is_system')
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'typeLabel' => $category->type->label(),
                    'parentId' => $category->parent_id,
                    'parentName' => $category->parent?->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'isSystem' => $category->is_system,
                ])
                ->values(),
            'categoryTypes' => collect(CategoryType::cases())
                ->map(fn (CategoryType $type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(SaveCategoryRequest $request, CreateCategory $createCategory): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();

        $createCategory->handle($team, [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
        ]);

        return back();
    }

    /**
     * Update the specified category.
     */
    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validated();

        $category->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
        ]);

        return back();
    }
}
