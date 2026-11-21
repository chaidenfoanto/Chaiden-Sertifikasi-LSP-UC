<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Models\GarageType;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\View\View;

/**
 * Shows the rental report with its four filters.
 */
class ReportController extends Controller
{
    /**
     * Show the report for the selected filters.
     */
    public function index(ReportFilterRequest $request, ReportService $reportService): View
    {
        $filters = $request->validated();

        return view('admin.reports.index', [
            'report' => $reportService->build($filters),
            'filters' => $filters,
            'garageTypes' => GarageType::orderBy('code')->get(),
            'renters' => User::where('role', User::ROLE_RENTER)
                ->where('status', User::STATUS_APPROVED)
                ->orderBy('full_name')
                ->get(),
        ]);
    }
}