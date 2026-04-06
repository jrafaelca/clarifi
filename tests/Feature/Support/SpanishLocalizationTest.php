<?php

use App\Domain\Accounts\Enums\AccountType;
use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('finance enums expose spanish labels', function () {
    expect(AccountType::Cash->label())->toBe('Efectivo')
        ->and(AccountType::CreditCard->label())->toBe('Tarjeta de credito')
        ->and(CategoryType::Income->label())->toBe('Ingreso')
        ->and(TransactionType::Transfer->label())->toBe('Transferencia')
        ->and(TransactionStatus::Confirmed->label())->toBe('Confirmada');
});

test('authentication screens render spanish copy', function () {
    expect(file_get_contents(resource_path('js/pages/auth/Login.vue')))
        ->toContain('Inicia sesion en tu cuenta')
        ->toContain('Correo electronico')
        ->toContain('Recordarme');

    expect(file_get_contents(resource_path('js/pages/auth/Register.vue')))
        ->toContain('Crea tu cuenta')
        ->toContain('Nombre completo');
});

test('transactions page exposes spanish labels and actions', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->get(route('transactions.index', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('transactionTypes.0.label', 'Ingreso')
            ->where('transactionTypes.2.label', 'Transferencia')
            ->where('transactionStatuses.0.label', 'Confirmada')
            ->where('transactionStatuses.1.label', 'Pendiente'));

    expect(file_get_contents(resource_path('js/pages/transactions/Index.vue')))
        ->toContain('Registrar movimiento')
        ->toContain('Aplicar filtros');
});
