<?php

namespace App\Application\Chat;

use App\Application\Accounts\CreateAccount;
use App\Application\Categories\CreateCategory;
use App\Application\Transactions\CreateTransactionFromSuggestion;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Models\Ai\IngestionBatch;
use App\Models\Ai\IngestionSuggestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveIngestionSuggestion
{
    public function __construct(
        protected CreateAccount $createAccount,
        protected CreateCategory $createCategory,
        protected CreateTransactionFromSuggestion $createTransactionFromSuggestion,
    ) {}

    /**
     * Approve and materialize the given suggestion.
     *
     * @param  array<string, mixed>  $edits
     */
    public function handle(IngestionSuggestion $suggestion, array $edits = []): IngestionSuggestion
    {
        return DB::transaction(function () use ($suggestion, $edits) {
            $suggestion->loadMissing('batch.team', 'batch.suggestions');

            $payload = $this->mergeEdits($suggestion, $edits);

            $materialized = match ($suggestion->kind) {
                'account' => $this->approveAccount($suggestion, $payload),
                'category' => $this->approveCategory($suggestion, $payload),
                'transaction' => $this->approveTransaction($suggestion, $payload),
                default => throw new RuntimeException("Unsupported suggestion kind [{$suggestion->kind}]."),
            };

            $suggestion->forceFill([
                'status' => 'approved',
                'payload_json' => $payload,
                'materialized_model_type' => $materialized::class,
                'materialized_model_id' => $materialized->getKey(),
                'approved_at' => now(),
                'rejected_at' => null,
            ])->save();

            $this->syncBatchStatus($suggestion->batch);

            return $suggestion->fresh(['batch', 'batch.suggestions']);
        });
    }

    /**
     * Reject the given suggestion.
     */
    public function reject(IngestionSuggestion $suggestion): IngestionSuggestion
    {
        $suggestion->loadMissing('batch.suggestions');

        $suggestion->forceFill([
            'status' => 'rejected',
            'approved_at' => null,
            'rejected_at' => now(),
        ])->save();

        $this->syncBatchStatus($suggestion->batch);

        return $suggestion->fresh(['batch', 'batch.suggestions']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function approveAccount(IngestionSuggestion $suggestion, array $payload): Account
    {
        return $this->createAccount->handle($suggestion->batch->team, [
            'name' => $payload['name'],
            'institution' => $payload['institution'] ?? null,
            'type' => $payload['type'] ?? 'bank',
            'currency' => $payload['currency'] ?? $suggestion->batch->team->currency,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ], matchExisting: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function approveCategory(IngestionSuggestion $suggestion, array $payload): Category
    {
        return $this->createCategory->handle($suggestion->batch->team, [
            'name' => $payload['name'],
            'type' => $payload['type'] ?? 'expense',
            'icon' => $payload['icon'] ?? null,
            'color' => $payload['color'] ?? null,
        ], matchExisting: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function approveTransaction(IngestionSuggestion $suggestion, array $payload): Model
    {
        $account = $this->resolveAccount($suggestion->batch, $payload);
        $category = $this->resolveCategory($suggestion->batch, $payload);

        return $this->createTransactionFromSuggestion->handle(
            $suggestion->batch->team,
            $payload,
            $account,
            $category,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveAccount(IngestionBatch $batch, array $payload): Account
    {
        if (isset($payload['account_id'])) {
            return Account::query()
                ->forTeam($batch->team)
                ->findOrFail($payload['account_id']);
        }

        if (isset($payload['account_ref'])) {
            $suggestion = $batch->suggestions
                ->firstWhere('suggestion_key', $payload['account_ref']);

            if ($suggestion === null) {
                throw new RuntimeException('The referenced account suggestion could not be found.');
            }

            if ($suggestion->status !== 'approved') {
                $this->handle($suggestion);
            }

            return Account::query()->findOrFail($suggestion->fresh()->materialized_model_id);
        }

        if (! empty($payload['account_name'])) {
            return $this->createAccount->handle($batch->team, [
                'name' => $payload['account_name'],
                'institution' => null,
                'type' => $payload['account_type'] ?? 'bank',
                'currency' => $batch->team->currency,
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ], matchExisting: true);
        }

        throw new RuntimeException('The transaction suggestion does not have a resolved account.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveCategory(IngestionBatch $batch, array $payload): ?Category
    {
        if (isset($payload['category_id']) && $payload['category_id'] !== null) {
            return Category::query()->findOrFail($payload['category_id']);
        }

        if (! isset($payload['category_ref'])) {
            if (! empty($payload['category_name'])) {
                return $this->createCategory->handle($batch->team, [
                    'name' => $payload['category_name'],
                    'type' => $payload['category_type'] ?? $payload['type'] ?? 'expense',
                ], matchExisting: true);
            }

            return null;
        }

        $suggestion = $batch->suggestions
            ->firstWhere('suggestion_key', $payload['category_ref']);

        if ($suggestion === null) {
            throw new RuntimeException('The referenced category suggestion could not be found.');
        }

        if ($suggestion->status !== 'approved') {
            $this->handle($suggestion);
        }

        return Category::query()->findOrFail($suggestion->fresh()->materialized_model_id);
    }

    /**
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    protected function mergeEdits(IngestionSuggestion $suggestion, array $edits): array
    {
        $payload = $suggestion->payload_json ?? [];

        $allowed = match ($suggestion->kind) {
            'account' => ['name', 'institution', 'type', 'currency'],
            'category' => ['name', 'type', 'icon', 'color'],
            'transaction' => [
                'transaction_date',
                'description',
                'amount',
                'type',
                'status',
                'notes',
                'account_name',
                'account_type',
                'account_id',
                'account_ref',
                'category_name',
                'category_type',
                'category_id',
                'category_ref',
                'attachment_path',
            ],
            default => [],
        };

        foreach ($allowed as $key) {
            if (array_key_exists($key, $edits)) {
                $payload[$key] = $edits[$key];
            }
        }

        return $payload;
    }

    protected function syncBatchStatus(IngestionBatch $batch): void
    {
        $batch->load('suggestions');

        $statuses = $batch->suggestions->pluck('status');

        $batch->status = match (true) {
            $statuses->isEmpty() => 'draft',
            $statuses->every(fn (string $status) => $status === 'approved') => 'confirmed',
            $statuses->every(fn (string $status) => $status === 'rejected') => 'rejected',
            $statuses->contains('approved') => 'partially_confirmed',
            default => 'draft',
        };

        $batch->processed_at ??= now();
        $batch->save();
    }
}
