<?php

namespace App\Models\Ai;

use Database\Factories\Ai\IngestionSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'batch_id',
    'suggestion_key',
    'kind',
    'status',
    'confidence',
    'source_excerpt',
    'payload_json',
    'materialized_model_type',
    'materialized_model_id',
    'approved_at',
    'rejected_at',
])]
class IngestionSuggestion extends Model
{
    /** @use HasFactory<IngestionSuggestionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_ingestion_suggestions';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): IngestionSuggestionFactory
    {
        return IngestionSuggestionFactory::new();
    }

    /**
     * Get the batch that owns the suggestion.
     *
     * @return BelongsTo<IngestionBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(IngestionBatch::class, 'batch_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'payload_json' => 'array',
            'approved_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
        ];
    }
}
