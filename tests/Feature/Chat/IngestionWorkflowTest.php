<?php

use App\Ai\Pipelines\FinanceImportPipeline;
use App\Domain\Transactions\Models\Transaction;
use App\Models\Ai\IngestionBatch;
use App\Models\Ai\IngestionSuggestion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;

test('import prompts create an ingestion batch response in the chat flow', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->forceFill([
        'openai_api_key_encrypted' => 'workspace-import-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    $batch = IngestionBatch::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'conversation_id' => fake()->uuid(),
        'source_kind' => 'csv',
        'status' => 'processing',
        'summary' => null,
    ]);

    $batch->files()->create([
        'disk' => 'local',
        'path' => 'ai-ingestion/test/transactions.csv',
        'mime_type' => 'text/csv',
        'original_name' => 'transactions.csv',
        'size_bytes' => 1024,
    ]);

    $this->mock(FinanceImportPipeline::class, function (MockInterface $mock) use ($batch) {
        $mock->shouldReceive('shouldUseImportFlow')
            ->once()
            ->andReturnTrue();

        $mock->shouldReceive('createBatch')
            ->once()
            ->andReturn($batch->fresh(['files', 'suggestions']));
    });

    $response = $this->actingAs($user)->post(route('chat.messages.store', [
        'current_team' => $team->slug,
    ]), [
        'prompt' => 'Importa estos movimientos',
        'attachments' => [
            UploadedFile::fake()->create('transactions.csv', 5, 'text/csv'),
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('assistantMessage.content', 'Estoy procesando la informacion y voy a dejar todo en borrador para tu revision.')
        ->assertJsonPath('batch.id', $batch->id)
        ->assertJsonPath('batch.status', 'processing');
});

test('approving an ingestion batch materializes accounts categories and transactions', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $batch = IngestionBatch::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'conversation_id' => fake()->uuid(),
        'source_kind' => 'text',
        'status' => 'draft',
    ]);

    IngestionSuggestion::factory()->create([
        'batch_id' => $batch->id,
        'suggestion_key' => 'account-1',
        'kind' => 'account',
        'payload_json' => [
            'name' => 'Cuenta Sueldo',
            'institution' => 'Banco Estado',
            'type' => 'bank',
            'currency' => $team->currency,
        ],
    ]);

    IngestionSuggestion::factory()->create([
        'batch_id' => $batch->id,
        'suggestion_key' => 'category-1',
        'kind' => 'category',
        'payload_json' => [
            'name' => 'Supermercado',
            'type' => 'expense',
        ],
    ]);

    IngestionSuggestion::factory()->create([
        'batch_id' => $batch->id,
        'suggestion_key' => 'transaction-1',
        'kind' => 'transaction',
        'payload_json' => [
            'transaction_date' => now()->toDateString(),
            'description' => 'Compra en supermercado',
            'amount' => '24500.00',
            'type' => 'expense',
            'status' => 'confirmed',
            'notes' => null,
            'account_name' => 'Cuenta Sueldo',
            'account_ref' => 'account-1',
            'category_name' => 'Supermercado',
            'category_ref' => 'category-1',
            'attachment_path' => null,
        ],
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.ingestion-batches.approve-all', [
            'current_team' => $team->slug,
            'ingestionBatch' => $batch->id,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('batch.status', 'confirmed')
        ->assertJsonPath('batch.counts.approved', 3);

    expect($team->accounts()->where('name', 'Cuenta Sueldo')->exists())->toBeTrue()
        ->and($team->categories()->where('name', 'Supermercado')->exists())->toBeTrue()
        ->and(Transaction::query()->forTeam($team)->where('description', 'Compra en supermercado')->exists())->toBeTrue()
        ->and($batch->refresh()->status)->toBe('confirmed');
});

test('users can not inspect another users ingestion batch', function () {
    $owner = User::factory()->create();
    $team = $owner->currentTeam;

    $intruder = User::factory()->create();
    $team->members()->attach($intruder, ['role' => 'member']);
    $intruder->switchTeam($team);

    $batch = IngestionBatch::factory()->create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'conversation_id' => fake()->uuid(),
    ]);

    $this->actingAs($intruder)
        ->get(route('chat.ingestion-batches.show', [
            'current_team' => $team->slug,
            'ingestionBatch' => $batch->id,
        ]))
        ->assertNotFound();
});
