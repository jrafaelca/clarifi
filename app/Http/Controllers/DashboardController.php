<?php

namespace App\Http\Controllers;

use App\Application\Dashboard\GetDashboardSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the ClariFi dashboard.
     */
    public function index(Request $request, GetDashboardSummary $getDashboardSummary): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();

        return Inertia::render('Dashboard', $getDashboardSummary->handle($team));
    }
}
