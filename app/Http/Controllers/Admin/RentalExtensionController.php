<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Models\Rental;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets the admin extend rentals that are active today.
 */
class RentalExtensionController extends Controller
{
    /**
     * List rentals that cover today, the ones that can be extended.
     */
    public function index(): View
    {
        $rentals = Rental::activeOn(CarbonImmutable::today())
            ->with(['user', 'vehicle', 'garage.type'])
            ->orderBy('end_date')
            ->get();

        return view('admin.rentals.index', compact('rentals'));
    }

    /**
     * JSON preview of the extra cost for a new end date.
     */
    public function quote(Request $request, Rental $rental, RentalService $rentalService): JsonResponse
    {
        $data = $request->validate(['new_end_date' => ['required', 'date_format:Y-m-d']]);

        return response()->json($rentalService->quoteExtension($rental, $data['new_end_date']));
    }

    /**
     * Extend the rental through the service.
     */
    public function store(ExtendRentalRequest $request, Rental $rental, RentalService $rentalService): RedirectResponse
    {
        $extension = $rentalService->extend(
            $rental,
            $request->validated('new_end_date'),
            $request->file('payment_proof')
        );

        return redirect()
            ->route('admin.rentals.index')
            ->with('success', 'Rental extended. Additional cost Rp '
                . number_format($extension->additional_cost, 0, ',', '.') . '.');
    }
}