<?php

namespace App\Ai\Pipelines;

use App\Ai\Agents\FinanceImportAgent;
use App\Ai\Support\TeamAiProviderFactory;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Jobs\ProcessIngestionBatch;
use App\Models\Ai\IngestionBatch;
use App\Models\Ai\IngestionFile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Files\File;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Prompts\AgentPrompt;
use RuntimeException;
use Throwable;

class FinanceImportPipeline
{
    public function __construct(
        protected TeamAiProviderFactory $teamAiProviderFactory,
        protected FilesystemFactory $filesystem,
    ) {}

    /**
     * Determine whether the prompt should enter import mode.
     *
     * @param  array<int, UploadedFile>  $attachments
     */
    public function shouldUseImportFlow(string $prompt, array $attachments = []): bool
    {
        if ($attachments !== []) {
            return true;
        }

        return Str::of($prompt)
            ->lower()
            ->contains([
                'registr',
                'import',
                'agrega',
                'anade',
                'añade',
                'sube',
                'carga',
                'ticket',
                'boleta',
                'factura',
                'estado de cuenta',
                'csv',
                'gast',
                'pague',
                'pagué',
                'ingres',
                'recibi',
                'recibí',
            ]);
    }

    /**
     * Create a batch in processing state and dispatch its job.
     *
     * @param  array<int, UploadedFile>  $attachments
     */
    public function createBatch(
        Team $team,
        User $user,
        string $prompt,
        array $attachments,
        string $conversationId,
    ): IngestionBatch {
        $batch = IngestionBatch::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'source_kind' => $this->detectSourceKind($attachments),
            'status' => 'processing',
            'raw_prompt' => $prompt,
        ]);

        foreach ($attachments as $attachment) {
            $path = $attachment->store("ai-ingestion/{$team->id}");

            $batch->files()->create([
                'disk' => config('filesystems.default', 'local'),
                'path' => $path,
                'mime_type' => $attachment->getClientMimeType() ?: 'application/octet-stream',
                'original_name' => $attachment->getClientOriginalName(),
                'size_bytes' => $attachment->getSize() ?: 0,
            ]);
        }

        ProcessIngestionBatch::dispatch($batch);

        return $batch->fresh(['files', 'suggestions']);
    }

    /**
     * Process the given batch and persist draft suggestions.
     */
    public function process(IngestionBatch $batch): IngestionBatch
    {
        $batch->loadMissing('team', 'user', 'files');

        try {
            if (! $batch->team->hasAiConfiguration()) {
                throw new RuntimeException('La IA no esta configurada para este espacio de trabajo.');
            }

            $structured = $this->extractSuggestions($batch);

            $this->persistSuggestions($batch, $structured);

            $batch->forceFill([
                'status' => 'draft',
                'summary' => $structured['summary'] ?? 'Se generaron sugerencias pendientes de confirmacion.',
                'error_message' => null,
                'processed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $batch->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 500),
                'processed_at' => now(),
            ])->save();
        }

        return $batch->fresh(['files', 'suggestions']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractSuggestions(IngestionBatch $batch): array
    {
        $csvPreview = $this->csvPreview($batch->files);

        $structuredAgent = new FinanceImportAgent($batch->team);

        $provider = $this->teamAiProviderFactory->forAgent($structuredAgent, $batch->team);
        $attachments = $this->aiAttachments($batch->files);
        $prompt = trim(implode("\n\n", array_filter([
            $batch->raw_prompt,
            $csvPreview === '' ? null : "CSV parsed preview:\n{$csvPreview}",
        ])));

        $response = $provider->prompt(new AgentPrompt(
            $structuredAgent,
            $prompt !== '' ? $prompt : 'Extract draft financial data from the attached inputs.',
            $attachments,
            $provider,
            $batch->team->ai_model,
            120,
        ));

        return $response->toArray();
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    protected function persistSuggestions(IngestionBatch $batch, array $structured): void
    {
        $batch->loadMissing('team');

        $batch->suggestions()->delete();

        $existingAccounts = Account::query()
            ->forTeam($batch->team)
            ->get();

        $existingCategories = Category::query()
            ->where(function ($query) use ($batch) {
                $query->whereNull('team_id')
                    ->orWhere('team_id', $batch->team_id);
            })
            ->get();

        $knownAccountIds = [];
        $knownCategoryIds = [];
        $createdAccountSuggestions = [];
        $createdCategorySuggestions = [];

        foreach (($structured['accounts'] ?? []) as $account) {
            $name = trim((string) ($account['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $match = $existingAccounts->first(fn (Account $existing) => $this->normalize($existing->name) === $this->normalize($name)
                && $this->normalize($existing->institution) === $this->normalize($account['institution'] ?? null));

            if ($match !== null) {
                $knownAccountIds[$this->normalize($name)] = $match->id;

                continue;
            }

            $key = 'account-'.(count($createdAccountSuggestions) + 1);

            $batch->suggestions()->create([
                'suggestion_key' => $key,
                'kind' => 'account',
                'status' => 'draft',
                'confidence' => null,
                'source_excerpt' => null,
                'payload_json' => [
                    'name' => $name,
                    'institution' => $account['institution'] ?? null,
                    'type' => $account['type'] ?? 'bank',
                    'currency' => $account['currency'] ?? $batch->team->currency,
                ],
            ]);

            $createdAccountSuggestions[$this->normalize($name)] = $key;
        }

        foreach (($structured['categories'] ?? []) as $category) {
            $name = trim((string) ($category['name'] ?? ''));
            $type = (string) ($category['type'] ?? 'expense');

            if ($name === '') {
                continue;
            }

            $match = $existingCategories->first(fn (Category $existing) => $this->normalize($existing->name) === $this->normalize($name)
                && $existing->type->value === $type);

            if ($match !== null) {
                $knownCategoryIds[$this->normalize($name).'|'.$type] = $match->id;

                continue;
            }

            $key = 'category-'.(count($createdCategorySuggestions) + 1);

            $batch->suggestions()->create([
                'suggestion_key' => $key,
                'kind' => 'category',
                'status' => 'draft',
                'confidence' => null,
                'source_excerpt' => null,
                'payload_json' => [
                    'name' => $name,
                    'type' => $type,
                    'parent_name' => $category['parent_name'] ?? null,
                    'icon' => $category['icon'] ?? null,
                    'color' => $category['color'] ?? null,
                ],
            ]);

            $createdCategorySuggestions[$this->normalize($name).'|'.$type] = $key;
        }

        foreach (($structured['transactions'] ?? []) as $transaction) {
            $transactionType = (string) ($transaction['type'] ?? 'expense');
            $accountName = trim((string) ($transaction['account_name'] ?? ''));
            $categoryName = trim((string) ($transaction['category_name'] ?? ''));

            $accountId = $accountName === '' ? null : ($knownAccountIds[$this->normalize($accountName)] ?? null);
            $accountRef = $accountName === '' ? null : ($createdAccountSuggestions[$this->normalize($accountName)] ?? null);

            if ($accountId === null && $accountRef === null && $accountName !== '') {
                $accountRef = 'account-'.(count($createdAccountSuggestions) + 1);

                $batch->suggestions()->create([
                    'suggestion_key' => $accountRef,
                    'kind' => 'account',
                    'status' => 'draft',
                    'confidence' => null,
                    'source_excerpt' => $transaction['source_excerpt'] ?? null,
                    'payload_json' => [
                        'name' => $accountName,
                        'institution' => null,
                        'type' => $transaction['account_type'] ?? 'bank',
                        'currency' => $batch->team->currency,
                    ],
                ]);

                $createdAccountSuggestions[$this->normalize($accountName)] = $accountRef;
            }

            $categoryId = null;
            $categoryRef = null;

            if ($categoryName !== '') {
                $categoryKey = $this->normalize($categoryName).'|'.$transactionType;
                $categoryId = $knownCategoryIds[$categoryKey] ?? null;
                $categoryRef = $createdCategorySuggestions[$categoryKey] ?? null;

                if ($categoryId === null && $categoryRef === null) {
                    $categoryRef = 'category-'.(count($createdCategorySuggestions) + 1);

                    $batch->suggestions()->create([
                        'suggestion_key' => $categoryRef,
                        'kind' => 'category',
                        'status' => 'draft',
                        'confidence' => null,
                        'source_excerpt' => $transaction['source_excerpt'] ?? null,
                        'payload_json' => [
                            'name' => $categoryName,
                            'type' => $transaction['category_type'] ?? $transactionType,
                            'icon' => null,
                            'color' => null,
                        ],
                    ]);

                    $createdCategorySuggestions[$categoryKey] = $categoryRef;
                }
            }

            $batch->suggestions()->create([
                'suggestion_key' => 'transaction-'.($batch->suggestions()->where('kind', 'transaction')->count() + 1),
                'kind' => 'transaction',
                'status' => 'draft',
                'confidence' => isset($transaction['confidence'])
                    ? round((float) $transaction['confidence'], 2)
                    : null,
                'source_excerpt' => $transaction['source_excerpt'] ?? null,
                'payload_json' => [
                    'transaction_date' => $transaction['transaction_date'] ?? now()->toDateString(),
                    'description' => $transaction['description'] ?? 'Movimiento importado',
                    'amount' => $this->normalizeAmount((string) ($transaction['amount'] ?? '0')),
                    'type' => $transactionType,
                    'status' => $transaction['status'] ?? 'confirmed',
                    'notes' => $transaction['notes'] ?? null,
                    'account_name' => $accountName,
                    'account_id' => $accountId,
                    'account_ref' => $accountRef,
                    'category_name' => $categoryName !== '' ? $categoryName : null,
                    'category_id' => $categoryId,
                    'category_ref' => $categoryRef,
                    'attachment_path' => $this->defaultAttachmentPath($batch),
                ],
            ]);
        }
    }

    /**
     * @param  Collection<int, IngestionFile>  $files
     * @return array<int, File>
     */
    protected function aiAttachments(Collection $files): array
    {
        return $files
            ->reject(fn (IngestionFile $file) => Str::contains($file->mime_type, 'csv'))
            ->map(function (IngestionFile $file) {
                $path = $this->filesystem->disk($file->disk)->path($file->path);

                return Str::startsWith($file->mime_type, 'image/')
                    ? Image::fromPath($path, $file->mime_type)
                    : Document::fromPath($path)->withMimeType($file->mime_type);
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, IngestionFile>  $files
     */
    protected function csvPreview(Collection $files): string
    {
        $previews = $files
            ->filter(fn (IngestionFile $file) => Str::contains($file->mime_type, 'csv') || Str::endsWith(Str::lower($file->original_name), '.csv'))
            ->map(function (IngestionFile $file) {
                $fullPath = $this->filesystem->disk($file->disk)->path($file->path);

                $handle = fopen($fullPath, 'r');

                if ($handle === false) {
                    return null;
                }

                $header = fgetcsv($handle) ?: [];
                $rows = [];

                while (($row = fgetcsv($handle)) !== false && count($rows) < 20) {
                    $rows[] = array_combine($header, array_pad($row, count($header), null)) ?: $row;
                }

                fclose($handle);

                return json_encode([
                    'file' => $file->original_name,
                    'rows' => $rows,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            })
            ->filter()
            ->values();

        return $previews->implode("\n\n");
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    protected function detectSourceKind(array $attachments): string
    {
        if ($attachments === []) {
            return 'text';
        }

        $kinds = collect($attachments)
            ->map(function ($attachment) {
                $mime = $attachment->getClientMimeType() ?: '';

                return match (true) {
                    Str::startsWith($mime, 'image/') => 'image',
                    $mime === 'application/pdf' => 'pdf',
                    Str::contains($mime, 'csv') || Str::endsWith(Str::lower($attachment->getClientOriginalName()), '.csv') => 'csv',
                    default => 'mixed',
                };
            })
            ->unique();

        return $kinds->count() === 1
            ? $kinds->first()
            : 'mixed';
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

    protected function normalizeAmount(string $value): string
    {
        $normalized = Str::of($value)
            ->replaceMatches('/[^0-9,.\-]/', '')
            ->replace(',', '.')
            ->value();

        if (substr_count($normalized, '.') > 1) {
            $parts = explode('.', $normalized);
            $decimal = array_pop($parts);
            $normalized = implode('', $parts).'.'.$decimal;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    protected function defaultAttachmentPath(IngestionBatch $batch): ?string
    {
        $file = $batch->files->first();

        return $file?->path;
    }
}
