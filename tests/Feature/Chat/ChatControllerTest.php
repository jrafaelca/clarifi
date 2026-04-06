<?php

use App\Ai\Agents\FinanceAssistantAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('chat answers are returned and persisted in a remembered conversation', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->forceFill([
        'openai_api_key_encrypted' => 'test-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    FinanceAssistantAgent::fake(['You are within budget this month.']);

    $response = $this->actingAs($user)->postJson(route('chat.messages.store', [
        'current_team' => $team->slug,
    ]), [
        'prompt' => 'How am I doing this month?',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('assistantMessage.content', 'You are within budget this month.');

    expect(DB::table('agent_conversations')->where('user_id', $user->id)->count())->toBe(1)
        ->and(DB::table('agent_conversation_messages')->where('user_id', $user->id)->count())->toBe(2);

    FinanceAssistantAgent::assertPrompted('How am I doing this month?');
});

test('chat answers can be streamed and persisted in a remembered conversation', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->forceFill([
        'openai_api_key_encrypted' => 'test-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    FinanceAssistantAgent::fake(['You are within budget this month.']);

    $response = $this->actingAs($user)->post(route('chat.messages.stream', [
        'current_team' => $team->slug,
    ]), [
        'prompt' => 'How am I doing this month?',
    ], [
        'Accept' => 'text/event-stream',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertSuccessful();

    expect($response->headers->get('content-type'))->toContain('text/event-stream')
        ->and($response->streamedContent())->toContain('"type":"stream_event"')
        ->and($response->streamedContent())->toContain('"type":"final_message"')
        ->and($response->streamedContent())->toContain('You are within budget this month.');

    expect(DB::table('agent_conversations')->where('user_id', $user->id)->count())->toBe(1)
        ->and(DB::table('agent_conversation_messages')->where('user_id', $user->id)->count())->toBe(2);
});

test('chat telemetry is logged through the sdk middleware and events', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->forceFill([
        'openai_api_key_encrypted' => 'test-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    Log::spy();
    FinanceAssistantAgent::fake(['You are within budget this month.']);

    $this->actingAs($user)->postJson(route('chat.messages.store', [
        'current_team' => $team->slug,
    ]), [
        'prompt' => 'How am I doing this month?',
    ])->assertSuccessful();

    Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context) => $message === 'clarifi.ai.middleware.prompt'
        && $context['agent'] === FinanceAssistantAgent::class
        && $context['team_id'] === $team->id
        && $context['user_id'] === $user->id)->atLeast()->once();

    Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context) => $message === 'clarifi.ai.prompted'
        && $context['agent'] === FinanceAssistantAgent::class
        && $context['team_id'] === $team->id
        && $context['user_id'] === $user->id
        && isset($context['usage']))->atLeast()->once();
});

test('users can not continue another users conversation', function () {
    $foreignUser = User::factory()->create();
    $foreignTeam = $foreignUser->currentTeam;
    $foreignTeam->forceFill([
        'openai_api_key_encrypted' => 'foreign-test-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    FinanceAssistantAgent::fake(['First response.']);

    $this->actingAs($foreignUser)->postJson(route('chat.messages.store', [
        'current_team' => $foreignTeam->slug,
    ]), [
        'prompt' => 'Start a conversation',
    ])->assertSuccessful();

    $foreignConversationId = DB::table('agent_conversations')
        ->where('user_id', $foreignUser->id)
        ->value('id');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->forceFill([
        'openai_api_key_encrypted' => 'local-test-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    $response = $this->actingAs($user)->postJson(route('chat.messages.store', [
        'current_team' => $team->slug,
    ]), [
        'prompt' => 'Use the other conversation',
        'conversation_id' => $foreignConversationId,
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('conversation_id');
});

test('chat is blocked when the current workspace has no ai configuration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('chat.messages.store', [
            'current_team' => $user->currentTeam->slug,
        ]), [
            'prompt' => 'How am I doing this month?',
        ])
        ->assertStatus(503)
        ->assertJsonPath('message', 'La IA todavia no esta configurada para este espacio de trabajo.');
});

test('stream route falls back to ingestion mode for import prompts', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->forceFill([
        'openai_api_key_encrypted' => 'test-key',
        'openai_api_key_last4' => 'tkey',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4.1-mini',
    ])->save();

    $this->actingAs($user)
        ->postJson(route('chat.messages.stream', [
            'current_team' => $team->slug,
        ]), [
            'prompt' => 'Registra estos gastos para mi',
        ])
        ->assertStatus(409)
        ->assertJsonPath('shouldFallbackToImport', true);
});
