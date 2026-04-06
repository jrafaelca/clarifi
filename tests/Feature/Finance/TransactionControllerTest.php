<?php

use App\Domain\Accounts\Enums\AccountType;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Domain\Transactions\Enums\TransactionFlow;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('users can record an expense and the account balance is recalculated', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $account = Account::factory()->forTeam($team)->create([
        'type' => AccountType::Bank,
        'initial_balance' => 1000,
        'current_balance' => 1000,
    ]);

    $category = Category::factory()->forTeam($team)->create([
        'type' => CategoryType::Expense,
    ]);

    $response = $this->actingAs($user)->post(route('transactions.store', [
        'current_team' => $team->slug,
    ]), [
        'type' => TransactionType::Expense->value,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => 125,
        'transaction_date' => now()->toDateString(),
        'description' => 'Groceries',
        'status' => 'confirmed',
        'attachment' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->type)->toBe(TransactionType::Expense)
        ->and($transaction->attachment_path)->not->toBeNull()
        ->and($account->fresh()->current_balance)->toBe('875.00');
});

test('users can record transfers as paired transactions and both balances are recalculated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $sourceAccount = Account::factory()->forTeam($team)->create([
        'type' => AccountType::Bank,
        'initial_balance' => 1000,
        'current_balance' => 1000,
    ]);

    $destinationAccount = Account::factory()->forTeam($team)->create([
        'type' => AccountType::Savings,
        'initial_balance' => 200,
        'current_balance' => 200,
    ]);

    $response = $this->actingAs($user)->post(route('transactions.store', [
        'current_team' => $team->slug,
    ]), [
        'type' => TransactionType::Transfer->value,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $destinationAccount->id,
        'amount' => 125,
        'transaction_date' => now()->toDateString(),
        'description' => 'Savings transfer',
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();

    $transactions = Transaction::query()
        ->orderBy('id')
        ->get();

    expect($transactions)->toHaveCount(2)
        ->and($transactions[0]->type)->toBe(TransactionType::Transfer)
        ->and($transactions[0]->direction)->toBe(TransactionFlow::Debit)
        ->and($transactions[1]->direction)->toBe(TransactionFlow::Credit)
        ->and($transactions[0]->transfer_group_uuid)->toBe($transactions[1]->transfer_group_uuid)
        ->and($sourceAccount->fresh()->current_balance)->toBe('875.00')
        ->and($destinationAccount->fresh()->current_balance)->toBe('325.00');
});

test('users can not submit transaction accounts from another workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $foreignUser = User::factory()->create();
    $foreignAccount = Account::factory()->forTeam($foreignUser->currentTeam)->create();

    $response = $this->actingAs($user)->post(route('transactions.store', [
        'current_team' => $team->slug,
    ]), [
        'type' => TransactionType::Expense->value,
        'account_id' => $foreignAccount->id,
        'amount' => 50,
        'transaction_date' => now()->toDateString(),
        'description' => 'Invalid workspace account',
    ]);

    $response->assertSessionHasErrors('account_id');

    expect(Transaction::query()->count())->toBe(0);
});
