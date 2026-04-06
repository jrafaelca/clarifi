<?php

namespace App\Models\Ai;

use Database\Factories\Ai\IngestionFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'batch_id',
    'disk',
    'path',
    'mime_type',
    'original_name',
    'size_bytes',
])]
class IngestionFile extends Model
{
    /** @use HasFactory<IngestionFileFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_ingestion_files';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): IngestionFileFactory
    {
        return IngestionFileFactory::new();
    }

    /**
     * Get the batch that owns the file.
     *
     * @return BelongsTo<IngestionBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(IngestionBatch::class, 'batch_id');
    }
}
