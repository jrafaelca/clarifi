<?php

namespace App\Ai\Middleware;

use App\Ai\Support\AgentTelemetryContext;
use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class LogAgentPrompt
{
    /**
     * Handle the incoming prompt.
     */
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        Log::info('clarifi.ai.middleware.prompt', AgentTelemetryContext::fromPrompt($prompt));

        return $next($prompt)->then(function (AgentResponse $response) {
            Log::info('clarifi.ai.middleware.response', AgentTelemetryContext::fromResponse($response));
        });
    }
}
