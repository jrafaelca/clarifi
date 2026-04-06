<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogAgentPrompt;
use App\Ai\Prompts\FinanceAssistantInstructions;
use App\Ai\Tools\GetAccountsSummaryTool;
use App\Ai\Tools\GetDebtSummaryTool;
use App\Ai\Tools\GetGoalsOverviewTool;
use App\Ai\Tools\GetMonthlyBudgetStatusTool;
use App\Ai\Tools\GetRecentTransactionsTool;
use App\Models\Team;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

class FinanceAssistantAgent implements Agent, Conversational, HasMiddleware, HasProviderOptions, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        protected User $user,
        protected Team $team,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return FinanceAssistantInstructions::forWorkspace($this->team);
    }

    /**
     * Force the initial provider for the finance assistant.
     */
    public function provider(): Lab
    {
        return Lab::OpenAI;
    }

    /**
     * Get the middleware that should run for the finance assistant.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new LogAgentPrompt,
        ];
    }

    /**
     * Get provider-specific options for the finance assistant.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        if ($provider !== Lab::OpenAI && $provider !== Lab::OpenAI->value) {
            return [];
        }

        return [
            'temperature' => 0.2,
        ];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new GetAccountsSummaryTool($this->team),
            new GetRecentTransactionsTool($this->team),
            new GetMonthlyBudgetStatusTool($this->team),
            new GetGoalsOverviewTool($this->team),
            new GetDebtSummaryTool($this->team),
        ];
    }

    /**
     * Limit the number of remembered messages kept in prompt context.
     */
    protected function maxConversationMessages(): int
    {
        return 40;
    }

    /**
     * Get the workspace associated with the current agent invocation.
     */
    public function team(): Team
    {
        return $this->team;
    }

    /**
     * Get the user associated with the current agent invocation.
     */
    public function user(): User
    {
        return $this->user;
    }
}
