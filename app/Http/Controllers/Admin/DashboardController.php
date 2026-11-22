<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GarageService;
use Illuminate\View\View;

/**
 * Admin landing page that links to every admin feature.
 */
class DashboardController extends Controller
{
    /**
     * Show the dashboard after refreshing the stored garage statuses,
     * so a garage whose rental has ended is shown as available again.
     */
    public function index(GarageService $garageService): View
    {
        $garageService->syncStatuses();

        return view('admin.dashboard');
    }
}