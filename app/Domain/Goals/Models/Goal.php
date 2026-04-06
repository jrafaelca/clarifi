<?php

namespace App\Domain\Goals\Models;

use App\Domain\Goals\Enums\GoalStatus;
use App\Domain\Shared\Concerns\BelongsToWorkspace;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'name',
    'target_amount',
    'current_amount',
    'currency',
    'target_date',
    'notes',
    'status',
])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GoalFactory
    {
        return GoalFactory::new();
    }

    /**
     * Get the contributions for the goal.
     *
     * @return HasMany<GoalContribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'immutable_date',
            'status' => GoalStatus::class,
        ];
    }
}
