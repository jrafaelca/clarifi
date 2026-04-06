<?php

namespace App\Ai\Tools;

use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class WorkspaceReadTool
{
    public function __construct(
        protected Team $team,
    ) {}

    /**
     * Build a normalized month value from a user argument.
     */
    protected function resolveMonth(?string $value): CarbonImmutable
    {
        if (blank($value)) {
            return CarbonImmutable::now()->startOfMonth();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $value)->startOfMonth();
        } catch (Throwable) {
            return CarbonImmutable::now()->startOfMonth();
        }
    }

    /**
     * Clamp an integer argument to a safe range.
     */
    protected function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    /**
     * Log the tool usage and return a JSON string payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     */
    protected function respond(array $payload, array $context = []): string
    {
        Log::info('clarifi.ai.tool_invoked', [
            'tool' => static::class,
            'team_id' => $this->team->id,
            ...$context,
        ]);

        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES);
    }
}
