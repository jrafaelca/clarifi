<?php

namespace App\Http\Controllers\Settings;

use App\Application\Settings\UpdateWorkspaceCurrency;
use App\Http\Controllers\Controller;
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
        Gate::authorize('update', $team);

        return Inertia::render('settings/Workspace', [
            'workspace' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'currency' => $team->currency,
                'isPersonal' => $team->is_personal,
            ],
            'currencyOptions' => collect(config('clarifi.supported_currencies', []))
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
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
}
