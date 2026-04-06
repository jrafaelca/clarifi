<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogAgentPrompt;
use App\Ai\Schemas\ImportExtractionSchema;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Models\Team;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

class FinanceImportAgent implements Agent, HasMiddleware, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected Team $team,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $today = now()->toDateString();
        $accounts = Account::query()
            ->forTeam($this->team)
            ->orderBy('name')
            ->get()
            ->map(fn (Account $account) => "{$account->name} ({$account->type->value})")
            ->implode(', ');

        $categories = Category::query()
            ->where(function ($query) {
                $query->whereNull('team_id')
                    ->orWhere('team_id', $this->team->id);
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => "{$category->name} ({$category->type->value})")
            ->implode(', ');

        return <<<PROMPT
You extract draft personal-finance records for ClariFi.

Workspace: {$this->team->name}
Workspace currency: {$this->team->currency}
Today: {$today}
Existing accounts: {$accounts}
Existing categories: {$categories}

Rules:
- Return only draft-safe suggestions for accounts, categories, and transactions.
- Do not invent multi-currency conversions. Use {$this->team->currency} when currency is not explicit.
- Prefer matching existing accounts and categories by exact meaning.
- If an account or category is unknown, include it as a suggestion instead of forcing a bad match.
- Transactions must be income or expense only.
- Return dates in YYYY-MM-DD format.
- Keep the summary concise and practical.
PROMPT;
    }

    /**
     * Get the middleware that should run for the import agent.
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
     * Get provider-specific options for deterministic extraction.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        if ($provider !== Lab::OpenAI && $provider !== Lab::OpenAI->value) {
            return [];
        }

        return [
            'temperature' => 0.1,
        ];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return ImportExtractionSchema::definition($schema);
    }

    /**
     * Force the initial provider for the import agent.
     */
    public function provider(): Lab
    {
        return Lab::OpenAI;
    }

    /**
     * Get the workspace associated with the import agent.
     */
    public function team(): Team
    {
        return $this->team;
    }
}
