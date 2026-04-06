<?php

namespace App\Ai\Support;

use App\Models\Team;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Providers\TextProvider;

class TeamAiProviderFactory
{
    public function __construct(
        protected AiManager $manager,
    ) {}

    /**
     * Resolve the text provider configured for the given team.
     */
    public function forAgent(Agent $agent, Team $team): TextProvider
    {
        if (! $team->hasAiConfiguration()) {
            throw new \RuntimeException('AI is not configured for the current workspace.');
        }

        if ($team->ai_provider !== 'openai') {
            throw new \RuntimeException('Only OpenAI is supported for workspace AI configuration.');
        }

        $provider = $this->manager->createOpenaiDriver(array_replace(
            config('ai.providers.openai', []),
            [
                'name' => 'workspace-openai',
                'key' => $team->openai_api_key_encrypted,
            ],
        ));

        if ($this->manager->hasFakeGatewayFor($agent)) {
            return (clone $provider)->useTextGateway(
                $this->manager->fakeGatewayFor($agent),
            );
        }

        return $provider;
    }
}
