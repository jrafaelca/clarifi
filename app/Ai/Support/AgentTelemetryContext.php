<?php

namespace App\Ai\Support;

use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;

class AgentTelemetryContext
{
    /**
     * Build a telemetry context payload from an AI prompt.
     */
    public static function fromPrompt(AgentPrompt $prompt): array
    {
        return array_filter([
            'agent' => $prompt->agent::class,
            'provider' => $prompt->provider()->driver(),
            'model' => $prompt->model,
            'prompt_length' => mb_strlen($prompt->prompt),
            'attachments_count' => $prompt->attachments->count(),
            ...self::fromAgent($prompt->agent),
        ], static fn ($value) => $value !== null);
    }

    /**
     * Build a telemetry context payload from an AI response.
     */
    public static function fromResponse(StreamedAgentResponse|AgentResponse $response): array
    {
        return array_filter([
            'invocation_id' => $response->invocationId,
            'conversation_id' => $response->conversationId,
            'response_length' => mb_strlen($response->text),
            'tool_calls_count' => $response->toolCalls->count(),
            'usage' => $response->usage->toArray(),
        ], static fn ($value) => $value !== null);
    }

    /**
     * Build a telemetry context payload from a tool invocation event.
     */
    public static function fromToolInvocation(ToolInvoked $event): array
    {
        return array_filter([
            'invocation_id' => $event->invocationId,
            'tool_invocation_id' => $event->toolInvocationId,
            'tool' => $event->tool::class,
            'agent' => $event->agent::class,
            'arguments' => $event->arguments,
            ...self::fromAgent($event->agent),
        ], static fn ($value) => $value !== null);
    }

    /**
     * Build a telemetry context payload from a known agent instance.
     *
     * @return array<string, mixed>
     */
    public static function fromAgent(object $agent): array
    {
        return array_filter([
            'team_id' => method_exists($agent, 'team') ? $agent->team()->id : null,
            'user_id' => method_exists($agent, 'user') ? $agent->user()->id : null,
            'conversation_id' => method_exists($agent, 'currentConversation') ? $agent->currentConversation() : null,
        ], static fn ($value) => $value !== null);
    }
}
