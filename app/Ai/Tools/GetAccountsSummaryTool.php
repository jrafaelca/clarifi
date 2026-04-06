<?php

namespace App\Ai\Tools;

use App\Application\Support\Money;
use App\Domain\Accounts\Models\Account;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAccountsSummaryTool extends WorkspaceReadTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Summarize the workspace accounts and their current balances.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $includeInactive = (bool) ($request['include_inactive'] ?? false);

        $accounts = Account::query()
            ->forTeam($this->team)
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        $totalBalance = $accounts->reduce(
            fn (int $carry, Account $account) => $carry + Money::toCents($account->current_balance),
            0,
        );

        return $this->respond([
            'workspace' => [
                'name' => $this->team->name,
                'currency' => $this->team->currency,
            ],
            'totals' => [
                'accounts' => $accounts->count(),
                'balance' => Money::fromCents($totalBalance),
            ],
            'accounts' => $accounts->map(fn (Account $account) => [
                'name' => $account->name,
                'type' => $account->type->value,
                'balance' => $account->current_balance,
                'is_active' => $account->is_active,
                'institution' => $account->institution,
            ])->values()->all(),
        ], [
            'include_inactive' => $includeInactive,
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'include_inactive' => $schema->boolean()->required(),
        ];
    }
}
