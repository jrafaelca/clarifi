<?php

namespace App\Providers;

use App\Ai\Support\AgentTelemetryContext;
use App\Domain\Accounts\Models\Account;
use App\Domain\Budgets\Models\Budget;
use App\Domain\Categories\Models\Category;
use App\Domain\Debts\Models\Debt;
use App\Domain\Goals\Models\Goal;
use App\Domain\Transactions\Models\Transaction;
use App\Policies\AccountPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DebtPolicy;
use App\Policies\GoalPolicy;
use App\Policies\TransactionPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\ToolInvoked;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(Goal::class, GoalPolicy::class);
        Gate::policy(Debt::class, DebtPolicy::class);

        $this->configureDefaults();
        $this->registerAiTelemetry();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register AI SDK telemetry listeners for prompts, responses, and tools.
     */
    protected function registerAiTelemetry(): void
    {
        Event::listen(PromptingAgent::class, function (PromptingAgent $event): void {
            Log::info('clarifi.ai.prompting', AgentTelemetryContext::fromPrompt($event->prompt) + [
                'invocation_id' => $event->invocationId,
            ]);
        });

        Event::listen(StreamingAgent::class, function (StreamingAgent $event): void {
            Log::info('clarifi.ai.streaming', AgentTelemetryContext::fromPrompt($event->prompt) + [
                'invocation_id' => $event->invocationId,
            ]);
        });

        Event::listen(AgentPrompted::class, function (AgentPrompted $event): void {
            Log::info('clarifi.ai.prompted', AgentTelemetryContext::fromPrompt($event->prompt)
                + AgentTelemetryContext::fromResponse($event->response));
        });

        Event::listen(AgentStreamed::class, function (AgentStreamed $event): void {
            Log::info('clarifi.ai.streamed', AgentTelemetryContext::fromPrompt($event->prompt)
                + AgentTelemetryContext::fromResponse($event->response));
        });

        Event::listen(ToolInvoked::class, function (ToolInvoked $event): void {
            Log::info('clarifi.ai.sdk_tool_invoked', AgentTelemetryContext::fromToolInvocation($event));
        });
    }
}
