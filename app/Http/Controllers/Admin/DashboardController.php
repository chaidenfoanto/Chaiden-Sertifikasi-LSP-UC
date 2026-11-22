<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\GarageService;
use Illuminate\View\View;

/**
 * Admin landing page with an overview of today's rentals and pending work.
 */
class DashboardController extends Controller
{
    /**
     * Show the dashboard after refreshing the stored garage statuses,
     * so a garage whose rental has ended is shown as available again.
     */
    public function index(GarageService $garageService, DashboardService $dashboardService): View
    {
        $garageService->syncStatuses();

        return view('admin.dashboard', ['summary' => $dashboardService->summary()]);
    }
}