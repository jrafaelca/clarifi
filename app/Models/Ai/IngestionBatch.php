<?php

namespace App\Models\Ai;

use App\Domain\Shared\Concerns\BelongsToWorkspace;
use App\Models\User;
use Database\Factories\Ai\IngestionBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id',
    'user_id',
    'conversation_id',
    'source_kind',
    'status',
    'raw_prompt',
    'summary',
    'error_message',
    'processed_at',
])]
class IngestionBatch extends Model
{
    /** @use HasFactory<IngestionBatchFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_ingestion_batches';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): IngestionBatchFactory
    {
        return IngestionBatchFactory::new();
    }

    /**
     * Get the user that created the batch.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the files attached to the batch.
     *
     * @return HasMany<IngestionFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(IngestionFile::class, 'batch_id');
    }

    /**
     * Get the suggestions extracted from the batch.
     *
     * @return HasMany<IngestionSuggestion, $this>
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(IngestionSuggestion::class, 'batch_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'immutable_datetime',
        ];
    }
}
