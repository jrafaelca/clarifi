<?php

namespace App\Application\Chat;

use App\Models\Ai\IngestionBatch;

class ApproveIngestionBatch
{
    public function __construct(
        protected ApproveIngestionSuggestion $approveIngestionSuggestion,
    ) {}

    /**
     * Approve all draft suggestions in dependency order.
     */
    public function handle(IngestionBatch $batch): IngestionBatch
    {
        $batch->loadMissing('suggestions');

        foreach (['account', 'category', 'transaction'] as $kind) {
            $batch->suggestions
                ->where('kind', $kind)
                ->where('status', 'draft')
                ->each(fn ($suggestion) => $this->approveIngestionSuggestion->handle($suggestion));
        }

        return $batch->fresh(['files', 'suggestions']);
    }
}
