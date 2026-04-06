<?php

namespace App\Http\Controllers\Chat;

use App\Ai\Schemas\ChatIngestionSummarySchema;
use App\Application\Chat\ApproveIngestionBatch;
use App\Http\Controllers\Controller;
use App\Models\Ai\IngestionBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestionBatchController extends Controller
{
    /**
     * Show the current state of the ingestion batch.
     */
    public function show(Request $request, string $currentTeam, IngestionBatch $ingestionBatch): JsonResponse
    {
        $this->authorizeBatch($request, $ingestionBatch);

        return response()->json([
            'batch' => ChatIngestionSummarySchema::fromBatch(
                $ingestionBatch->load(['files', 'suggestions']),
            ),
        ]);
    }

    /**
     * Approve all draft suggestions in the batch.
     */
    public function approveAll(
        Request $request,
        string $currentTeam,
        IngestionBatch $ingestionBatch,
        ApproveIngestionBatch $approveIngestionBatch,
    ): JsonResponse {
        $this->authorizeBatch($request, $ingestionBatch);

        return response()->json([
            'batch' => ChatIngestionSummarySchema::fromBatch(
                $approveIngestionBatch->handle($ingestionBatch),
            ),
        ]);
    }

    protected function authorizeBatch(Request $request, IngestionBatch $ingestionBatch): void
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        abort_unless(
            $ingestionBatch->team_id === $team->id
                && $ingestionBatch->user_id === $request->user()->id,
            404,
        );
    }
}
