<?php

namespace App\Ai\Tools;

use App\Application\Support\Money;
use App\Domain\Goals\Models\Goal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetGoalsOverviewTool extends WorkspaceReadTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Summarize the workspace savings goals, their progress, and target gaps.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $goals = Goal::query()
            ->forTeam($this->team)
            ->orderBy('target_date')
            ->orderBy('name')
            ->get();

        $targetAmount = $goals->reduce(
            fn (int $carry, Goal $goal) => $carry + Money::toCents($goal->target_amount),
            0,
        );

        $currentAmount = $goals->reduce(
            fn (int $carry, Goal $goal) => $carry + Money::toCents($goal->current_amount),
            0,
        );

        return $this->respond([
            'workspace' => [
                'name' => $this->team->name,
                'currency' => $this->team->currency,
            ],
            'totals' => [
                'goals' => $goals->count(),
                'current_amount' => Money::fromCents($currentAmount),
                'target_amount' => Money::fromCents($targetAmount),
                'remaining_amount' => Money::fromCents(max(0, $targetAmount - $currentAmount)),
            ],
            'goals' => $goals->map(function (Goal $goal) {
                $target = Money::toCents($goal->target_amount);
                $current = Money::toCents($goal->current_amount);

                return [
                    'name' => $goal->name,
                    'current_amount' => $goal->current_amount,
                    'target_amount' => $goal->target_amount,
                    'remaining_amount' => Money::fromCents(max(0, $target - $current)),
                    'progress_percent' => $target > 0 ? round(($current / $target) * 100, 1) : 0,
                    'target_date' => $goal->target_date?->toDateString(),
                    'status' => $goal->status->value,
                ];
            })->values()->all(),
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
