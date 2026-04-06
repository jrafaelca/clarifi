<?php

use App\Ai\Agents\FinanceAssistantAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('chat answers are returned and persisted in a remembered conversation', function () {
    config()->set('ai.providers.openai.key', 'test-key');

    $user = User::factory()->create();
    $team = $user->currentTeam;

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

test('users can not continue another users conversation', function () {
    config()->set('ai.providers.openai.key', 'test-key');

    $foreignUser = User::factory()->create();
    $foreignTeam = $foreignUser->currentTeam;

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

    $response = $this->actingAs($user)->postJson(route('chat.messages.store', [
        'current_team' => $team->slug,
    ]), [
        'prompt' => 'Use the other conversation',
        'conversation_id' => $foreignConversationId,
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('conversation_id');
});
