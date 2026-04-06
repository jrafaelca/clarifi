<?php

use App\Domain\Accounts\Models\Account;
use App\Domain\Budgets\Models\Budget;
use App\Domain\Debts\Models\Debt;
use App\Domain\Goals\Models\Goal;
use App\Domain\Transactions\Models\Transaction;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('workspace settings page is displayed with supported currencies', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('workspace.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Workspace')
            ->where('workspace.currency', $user->currentTeam->currency)
            ->has('currencyOptions', 3),
        );
});

test('workspace currency can be updated and synced across financial records', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Account::factory()->forTeam($team)->create(['currency' => 'USD']);
    Budget::factory()->forTeam($team)->create(['currency' => 'USD']);
    Goal::factory()->forTeam($team)->create(['currency' => 'USD']);
    Debt::factory()->forTeam($team)->create(['currency' => 'USD']);
    Transaction::factory()->forTeam($team)->create(['currency' => 'USD']);

    $response = $this->actingAs($user)
        ->from(route('workspace.edit'))
        ->patch(route('workspace.update'), [
            'currency' => 'CLP',
        ]);

    $response->assertRedirect(route('workspace.edit'));
    $response->assertSessionHas('status', 'workspace-updated');

    expect($team->refresh()->currency)->toBe('CLP');
    expect(Account::query()->forTeam($team)->value('currency'))->toBe('CLP');
    expect(Budget::query()->forTeam($team)->value('currency'))->toBe('CLP');
    expect(Goal::query()->forTeam($team)->value('currency'))->toBe('CLP');
    expect(Debt::query()->forTeam($team)->value('currency'))->toBe('CLP');
    expect(Transaction::query()->forTeam($team)->value('currency'))->toBe('CLP');
});

test('workspace currency must be one of the supported currencies', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('workspace.edit'))
        ->patch(route('workspace.update'), [
            'currency' => 'ARS',
        ]);

    $response->assertRedirect(route('workspace.edit'));
    $response->assertSessionHasErrors('currency');
});

test('users who can not update the workspace receive forbidden responses', function () {
    $team = Team::factory()->create([
        'currency' => 'USD',
        'is_personal' => false,
    ]);

    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->patch(route('workspace.update'), [
            'currency' => 'CLP',
        ])
        ->assertForbidden();
});
