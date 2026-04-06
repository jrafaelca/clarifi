<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        return Inertia::render('settings/Workspace', [
            'workspace' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'currency' => $team->currency,
                'isPersonal' => $team->is_personal,
            ],
        ]);
    }
}
