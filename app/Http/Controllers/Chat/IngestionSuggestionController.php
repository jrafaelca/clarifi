<?php

namespace App\Http\Controllers\Chat;

use App\Ai\Schemas\ChatIngestionSummarySchema;
use App\Application\Chat\ApproveIngestionSuggestion;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\UpdateIngestionSuggestionRequest;
use App\Models\Ai\IngestionSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class IngestionSuggestionController extends Controller
{
    /**
     * Update the suggestion state from the chat review flow.
     */
    public function update(
        UpdateIngestionSuggestionRequest $request,
        string $currentTeam,
        IngestionSuggestion $ingestionSuggestion,
        ApproveIngestionSuggestion $approveIngestionSuggestion,
    ): JsonResponse {
        $this->authorizeSuggestion(
            $request->user()->currentTeam()->firstOrFail()->id,
            $request->user()->id,
            $ingestionSuggestion,
        );

        $suggestion = $request->validated('action') === 'approve'
            ? $approveIngestionSuggestion->handle(
                $ingestionSuggestion,
                $this->normalizedEdits($ingestionSuggestion, $request->input('edits', [])),
            )
            : $approveIngestionSuggestion->reject($ingestionSuggestion);

        return response()->json([
            'batch' => ChatIngestionSummarySchema::fromBatch(
                $suggestion->batch->fresh(['files', 'suggestions']),
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    protected function normalizedEdits(IngestionSuggestion $suggestion, array $edits): array
    {
        if ($suggestion->kind !== 'transaction') {
            return $edits;
        }

        $team = $suggestion->batch->team;

        if (isset($edits['account_name'])) {
            $account = Account::query()
                ->forTeam($team)
                ->get()
                ->first(fn (Account $candidate) => $this->normalize($candidate->name) === $this->normalize($edits['account_name']));

            $edits['account_id'] = $account?->id;
            $edits['account_ref'] = $account === null
                ? $suggestion->batch->suggestions
                    ->where('kind', 'account')
                    ->first(fn (IngestionSuggestion $candidate) => $this->normalize($candidate->payload_json['name'] ?? null) === $this->normalize($edits['account_name']))
                    ?->suggestion_key
                : null;
        }

        if (isset($edits['category_name'])) {
            $category = Category::query()
                ->where(function ($query) use ($team) {
                    $query->whereNull('team_id')
                        ->orWhere('team_id', $team->id);
                })
                ->get()
                ->first(fn (Category $candidate) => $this->normalize($candidate->name) === $this->normalize($edits['category_name']));

            $edits['category_id'] = $category?->id;
            $edits['category_ref'] = $category === null
                ? $suggestion->batch->suggestions
                    ->where('kind', 'category')
                    ->first(fn (IngestionSuggestion $candidate) => $this->normalize($candidate->payload_json['name'] ?? null) === $this->normalize($edits['category_name']))
                    ?->suggestion_key
                : null;
        }

        return $edits;
    }

    protected function authorizeSuggestion(int $teamId, int $userId, IngestionSuggestion $ingestionSuggestion): void
    {
        $ingestionSuggestion->loadMissing('batch', 'batch.team', 'batch.suggestions');

        abort_unless(
            $ingestionSuggestion->batch->team_id === $teamId
                && $ingestionSuggestion->batch->user_id === $userId,
            404,
        );
    }

    protected function normalize(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();
    }
}
