<?php

namespace App\Ai\Tools;

use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetRecentTransactionsTool extends WorkspaceReadTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List the most recent transactions in the workspace, optionally filtered by month or type.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $limit = $this->clamp((int) ($request['limit'] ?? 5), 1, 10);
        $month = $this->resolveMonth((string) ($request['month'] ?? ''));
        $type = (string) ($request['type'] ?? '');

        $transactions = Transaction::query()
            ->forTeam($this->team)
            ->with(['account', 'category', 'relatedAccount'])
            ->when(
                in_array($type, TransactionType::values(), true),
                fn ($query) => $query->where('type', $type),
            )
            ->whereBetween('transaction_date', [
                $month->startOfMonth()->toDateString(),
                $month->endOfMonth()->toDateString(),
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit($limit)
            ->get();

        return $this->respond([
            'workspace' => [
                'name' => $this->team->name,
                'currency' => $this->team->currency,
                'month' => $month->format('Y-m'),
            ],
            'transactions' => $transactions->map(fn (Transaction $transaction) => [
                'date' => $transaction->transaction_date->toDateString(),
                'description' => $transaction->description,
                'amount' => $transaction->amount,
                'type' => $transaction->type->value,
                'direction' => $transaction->direction->value,
                'account' => $transaction->account->name,
                'related_account' => $transaction->relatedAccount?->name,
                'category' => $transaction->category?->name,
                'status' => $transaction->status->value,
            ])->values()->all(),
        ], [
            'month' => $month->format('Y-m'),
            'type' => $type ?: null,
            'limit' => $limit,
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->min(1)->max(10)->required(),
            'month' => $schema->string()->nullable()->required(),
            'type' => $schema->string()->nullable()->required(),
        ];
    }
}
