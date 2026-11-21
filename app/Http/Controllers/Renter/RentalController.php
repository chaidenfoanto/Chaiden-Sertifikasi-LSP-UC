<?php

namespace App\Http\Controllers\Renter;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets a renter preview, create, and review garage rentals.
 */
class RentalController extends Controller
{
    /**
     * Rental history of the logged in renter.
     */
    public function index(Request $request): View
    {
        $rentals = $request->user()
            ->rentals()
            ->with(['vehicle', 'garage.type', 'items', 'extensions'])
            ->latest('start_date')
            ->latest('id')
            ->get();

        return view('renter.rentals.index', compact('rentals'));
    }

    /**
     * Show the new rental form.
     */
    public function create(): View
    {
        return view('renter.rentals.create', ['vehicleTypes' => Vehicle::TYPES]);
    }

    /**
     * JSON preview of dates, free garages, and total cost before saving.
     */
    public function quote(QuoteRentalRequest $request, RentalService $rentalService): JsonResponse
    {
        return response()->json($rentalService->quote($request->validated()));
    }

    /**
     * Save a new rental through the service.
     */
    public function store(StoreRentalRequest $request, RentalService $rentalService): RedirectResponse
    {
        $rental = $rentalService->create(
            $request->user(),
            $request->validated(),
            $request->file('payment_proof')
        );

        return redirect()
            ->route('renter.rentals.index')
            ->with('success', "Rental saved for garage {$rental->garage->code}. Total cost Rp "
                . number_format($rental->total_cost, 0, ',', '.') . '.');
    }
}