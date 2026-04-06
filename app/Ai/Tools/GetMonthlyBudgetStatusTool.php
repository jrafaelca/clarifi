<?php

namespace App\Ai\Tools;

use App\Application\Budgets\GetMonthlyBudgetStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetMonthlyBudgetStatusTool extends WorkspaceReadTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Show the monthly budget status, including budgeted, spent, and remaining amounts by category.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $month = $this->resolveMonth((string) ($request['month'] ?? ''));
        $status = app(GetMonthlyBudgetStatus::class)->handle($this->team, $month);

        return $this->respond($status, [
            'month' => $month->format('Y-m'),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()->nullable()->required(),
        ];
    }
}
