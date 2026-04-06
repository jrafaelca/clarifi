<?php

namespace App\Ai\Schemas;

use App\Models\Ai\IngestionBatch;
use App\Models\Ai\IngestionSuggestion;

class ChatIngestionSummarySchema
{
    /**
     * Transform the ingestion batch into a chat-friendly payload.
     *
     * @return array<string, mixed>
     */
    public static function fromBatch(IngestionBatch $batch): array
    {
        $batch->loadMissing('files', 'suggestions');

        return [
            'id' => $batch->id,
            'conversationId' => $batch->conversation_id,
            'status' => $batch->status,
            'sourceKind' => $batch->source_kind,
            'summary' => $batch->summary,
            'errorMessage' => $batch->error_message,
            'processedAt' => $batch->processed_at?->toIso8601String(),
            'createdAt' => $batch->created_at?->toIso8601String(),
            'files' => $batch->files->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mimeType' => $file->mime_type,
                'sizeBytes' => $file->size_bytes,
            ])->values()->all(),
            'counts' => [
                'draft' => $batch->suggestions->where('status', 'draft')->count(),
                'approved' => $batch->suggestions->where('status', 'approved')->count(),
                'rejected' => $batch->suggestions->where('status', 'rejected')->count(),
            ],
            'suggestions' => $batch->suggestions
                ->sortBy(['kind', 'id'])
                ->map(fn (IngestionSuggestion $suggestion) => [
                    'id' => $suggestion->id,
                    'key' => $suggestion->suggestion_key,
                    'kind' => $suggestion->kind,
                    'status' => $suggestion->status,
                    'confidence' => $suggestion->confidence,
                    'sourceExcerpt' => $suggestion->source_excerpt,
                    'payload' => $suggestion->payload_json,
                    'materializedModelType' => $suggestion->materialized_model_type,
                    'materializedModelId' => $suggestion->materialized_model_id,
                    'approvedAt' => $suggestion->approved_at?->toIso8601String(),
                    'rejectedAt' => $suggestion->rejected_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
