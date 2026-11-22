<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageBatchRequest;
use App\Models\Garage;
use App\Models\GarageType;
use App\Services\GarageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Lets the admin create garages in batches and see the prices per type.
 */
class GarageController extends Controller
{
    /**
     * Show the batch creation form and a summary per garage type.
     */
    public function create(GarageService $garageService): View
    {
        // Rentals end by themselves, so refresh the stored status before showing it.
        $garageService->syncStatuses();

        $types = GarageType::withCount([
            'garages',
            'garages as used_garages_count' => fn ($query) => $query->where('status', Garage::STATUS_USED),
        ])->orderBy('code')->get();

        return view('admin.garages.create', compact('types'));
    }

    /**
     * Create the garage batch through the service.
     */
    public function store(StoreGarageBatchRequest $request, GarageService $garageService): RedirectResponse
    {
        $data = $request->validated();
        $type = GarageType::findOrFail($data['garage_type_id']);
        $newPrice = (int) $data['daily_price'];

        // Remember the old state so the message can tell the admin what changed.
        $previousPrice = $type->daily_price === null ? null : (int) $type->daily_price;
        $existingCount = $type->garages()->count();

        $codes = $garageService->createBatch(
            $type,
            (int) $data['quantity'],
            $newPrice,
            isset($data['electricity_price_per_kwh']) ? (int) $data['electricity_price_per_kwh'] : null
        );

        $message = count($codes) . ' garage(s) created (' . $codes[0] . ' to ' . end($codes) . ').';

        // The price belongs to the type, so older garages follow the new price too.
        if ($previousPrice !== null && $existingCount > 0 && $previousPrice !== $newPrice) {
            $message .= " The daily price of the {$existingCount} existing garage(s) of this type changed from Rp "
                . number_format($previousPrice, 0, ',', '.') . ' to Rp ' . number_format($newPrice, 0, ',', '.') . '.';
        }

        return redirect()
            ->route('admin.garages.create')
            ->with('success', $message);
    }
}