<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace settings exposes ai status to team members', function () {
    $team = Team::factory()->create([
        'is_personal' => false,
    ]);

    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->get(route('workspace.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Workspace')
            ->where('canManageWorkspace', false)
            ->where('aiSettings.configured', false),
        );
});

test('workspace ai settings can be updated and encrypted', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $apiKey = 'sk-proj-test-key-1234567890abcdef1234';

    $response = $this->actingAs($user)
        ->from(route('workspace.edit'))
        ->patch(route('workspace.ai.update'), [
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4.1-mini',
            'openai_api_key' => $apiKey,
        ]);

    $response->assertRedirect(route('workspace.edit'));
    $response->assertSessionHas('status', 'workspace-ai-updated');

    expect($team->refresh()->ai_provider)->toBe('openai')
        ->and($team->ai_model)->toBe('gpt-4.1-mini')
        ->and($team->openai_api_key_last4)->toBe('1234')
        ->and(DB::table('teams')->where('id', $team->id)->value('openai_api_key_encrypted'))->not->toBe($apiKey);
});

test('members can not update or remove workspace ai settings', function () {
    $team = Team::factory()->create([
        'is_personal' => false,
        'openai_api_key_encrypted' => 'encrypted-key',
        'openai_api_key_last4' => '1234',
    ]);

    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->patch(route('workspace.ai.update'), [
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4.1-mini',
            'openai_api_key' => 'sk-proj-member-blocked-key-1234567890',
        ])
        ->assertForbidden();

    $this->actingAs($member)
        ->delete(route('workspace.ai.destroy'))
        ->assertForbidden();
});

test('admins can update and remove workspace ai settings', function () {
    $team = Team::factory()->create([
        'is_personal' => false,
    ]);

    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $admin = User::factory()->create();
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->switchTeam($team);

    $this->actingAs($admin)
        ->patch(route('workspace.ai.update'), [
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4.1',
            'openai_api_key' => 'sk-proj-admin-allowed-key-1234567890abcdef',
        ])
        ->assertRedirect(route('workspace.edit'))
        ->assertSessionHas('status', 'workspace-ai-updated');

    expect($team->refresh()->ai_model)->toBe('gpt-4.1')
        ->and($team->openai_api_key_last4)->toBe('cdef');

    $this->actingAs($admin)
        ->delete(route('workspace.ai.destroy'))
        ->assertRedirect(route('workspace.edit'))
        ->assertSessionHas('status', 'workspace-ai-removed');

    expect($team->refresh()->hasAiConfiguration())->toBeFalse();
});
