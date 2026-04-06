<?php

namespace App\Domain\Goals\Models;

use App\Domain\Accounts\Models\Account;
use App\Domain\Shared\Concerns\BelongsToWorkspace;
use Database\Factories\GoalContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id',
    'goal_id',
    'account_id',
    'amount',
    'contributed_on',
    'notes',
])]
class GoalContribution extends Model
{
    /** @use HasFactory<GoalContributionFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GoalContributionFactory
    {
        return GoalContributionFactory::new();
    }

    /**
     * Get the goal that owns the contribution.
     *
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * Get the account used to fund the contribution.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'contributed_on' => 'immutable_date',
        ];
    }
}
