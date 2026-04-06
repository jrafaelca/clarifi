<?php

namespace App\Http\Controllers\Settings;

use App\Application\Settings\UpdateWorkspaceCurrency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveWorkspaceAiSettingsRequest;
use App\Http\Requests\Settings\WorkspaceUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    /**
     * Display the current workspace details.
     */
    public function edit(Request $request): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();
        Gate::authorize('view', $team);

        return Inertia::render('settings/Workspace', [
            'workspace' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'currency' => $team->currency,
                'isPersonal' => $team->is_personal,
            ],
            'canManageWorkspace' => $request->user()->can('update', $team),
            'currencyOptions' => collect(config('clarifi.supported_currencies', []))
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'aiSettings' => [
                'configured' => $team->hasAiConfiguration(),
                'provider' => $team->ai_provider,
                'model' => $team->ai_model,
                'keyLast4' => $team->openai_api_key_last4,
            ],
            'aiModelOptions' => [
                ['value' => 'gpt-4.1-mini', 'label' => 'gpt-4.1-mini'],
                ['value' => 'gpt-4.1', 'label' => 'gpt-4.1'],
                ['value' => 'gpt-4o-mini', 'label' => 'gpt-4o-mini'],
            ],
        ]);
    }

    /**
     * Update the current workspace currency.
     */
    public function update(
        WorkspaceUpdateRequest $request,
        UpdateWorkspaceCurrency $updateWorkspaceCurrency,
    ): RedirectResponse {
        $team = $request->user()->currentTeam()->firstOrFail();

        $updateWorkspaceCurrency->handle($team, $request->validated('currency'));

        return to_route('workspace.edit')->with('status', 'workspace-updated');
    }

    /**
     * Update the workspace AI configuration.
     */
    public function updateAi(SaveWorkspaceAiSettingsRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        $team->forceFill([
            'ai_provider' => $request->validated('ai_provider'),
            'ai_model' => $request->validated('ai_model'),
            'openai_api_key_encrypted' => $request->validated('openai_api_key'),
            'openai_api_key_last4' => substr($request->validated('openai_api_key'), -4),
        ])->save();

        return to_route('workspace.edit')->with('status', 'workspace-ai-updated');
    }

    /**
     * Remove the workspace AI configuration.
     */
    public function destroyAi(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam()->firstOrFail();
        Gate::authorize('update', $team);

        $team->forceFill([
            'openai_api_key_encrypted' => null,
            'openai_api_key_last4' => null,
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4.1-mini',
        ])->save();

        return to_route('workspace.edit')->with('status', 'workspace-ai-removed');
    }
}
