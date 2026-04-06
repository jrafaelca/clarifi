<?php

namespace App\Ai\Tools;

use App\Application\Support\Money;
use App\Domain\Debts\Models\Debt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetDebtSummaryTool extends WorkspaceReadTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Summarize debt balances, minimum payments, and upcoming due dates for the workspace.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $debts = Debt::query()
            ->forTeam($this->team)
            ->orderBy('due_date')
            ->orderBy('name')
            ->get();

        $currentBalance = $debts->reduce(
            fn (int $carry, Debt $debt) => $carry + Money::toCents($debt->current_balance),
            0,
        );

        $minimumPayments = $debts->reduce(
            fn (int $carry, Debt $debt) => $carry + Money::toCents($debt->minimum_payment),
            0,
        );

        return $this->respond([
            'workspace' => [
                'name' => $this->team->name,
                'currency' => $this->team->currency,
            ],
            'totals' => [
                'debts' => $debts->count(),
                'current_balance' => Money::fromCents($currentBalance),
                'minimum_payments' => Money::fromCents($minimumPayments),
            ],
            'debts' => $debts->map(fn (Debt $debt) => [
                'name' => $debt->name,
                'lender' => $debt->lender,
                'current_balance' => $debt->current_balance,
                'minimum_payment' => $debt->minimum_payment,
                'interest_rate' => $debt->interest_rate,
                'due_date' => $debt->due_date?->toDateString(),
                'status' => $debt->status->value,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
