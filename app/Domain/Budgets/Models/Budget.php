<?php

namespace App\Domain\Budgets\Models;

use App\Domain\Categories\Models\Category;
use App\Domain\Shared\Concerns\BelongsToWorkspace;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'category_id',
    'month',
    'currency',
    'amount',
])]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BudgetFactory
    {
        return BudgetFactory::new();
    }

    /**
     * Get the category for the budget.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'immutable_date',
            'amount' => 'decimal:2',
        ];
    }
}
