<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\GarageType;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\RentalItem;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Business logic for renting garages and extending rentals.
 */
class RentalService
{
    public function __construct(
        private readonly RentalPlanner $planner,
        private readonly RentalPricing $pricing,
    ) {
    }

    /**
     * Preview a rental: the date plan and the available garages with their cost.
     *
     * @param array<string, mixed> $data Validated quote input.
     * @return array<string, mixed>
     */
    public function quote(array $data): array
    {
        $start = $this->resolveStartDate($data['start_date'] ?? null);
        $plan = $this->planner->plan($start, $data['rental_type'], (int) $data['quantity']);
        $end = $this->planner->endDate($plan);

        $subtotals = [];
        $garages = [];

        foreach ($this->availableGarages($data['vehicle_type'], $start, $end) as $garage) {
            $type = $garage->type;

            // Types without a price set by the admin cannot be rented yet.
            if ($type->daily_price === null) {
                continue;
            }

            $subtotals[$type->id] ??= $this->pricing->subtotal(
                $this->pricing->priceItems($plan, $type->daily_price)
            );

            $charging = $this->chargingFor(
                $data['vehicle_type'],
                $type,
                $data['battery_capacity_kwh'] ?? null,
                $data['remaining_battery_kwh'] ?? null
            );

            $garages[] = [
                'id' => $garage->id,
                'code' => $garage->code,
                'type' => $type->name,
                'subtotal' => $subtotals[$type->id],
                'charging_cost' => $charging,
                'total' => $subtotals[$type->id] + $charging,
            ];
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'items' => $this->formatItems($plan),
            'garages' => $garages,
        ];
    }

    /**
     * Garages compatible with the vehicle and free for the whole date range.
     *
     * @return Collection<int, Garage>
     */
    public function availableGarages(string $vehicleType, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return Garage::with('type')
            ->whereHas('type', fn ($query) => $query->whereIn('name', Vehicle::compatibleGarageTypes($vehicleType)))
            ->whereDoesntHave('rentals', fn ($query) => $query->overlapping($start, $end))
            ->orderBy('code')
            ->get();
    }

    /**
     * Save a new rental with its parts and payment proof.
     *
     * @param array<string, mixed> $data Validated store input.
     *
     * @throws ValidationException When a business rule is broken.
     */
    public function create(User $renter, array $data, UploadedFile $proof): Rental
    {
        $start = $this->resolveStartDate($data['start_date'] ?? null);
        $plan = $this->planner->plan($start, $data['rental_type'], (int) $data['quantity']);
        $end = $this->planner->endDate($plan);
        $storedPath = null;

        try {
            return DB::transaction(function () use ($renter, $data, $proof, $start, $plan, $end, &$storedPath): Rental {
                $garage = Garage::with('type')->whereKey($data['garage_id'])->lockForUpdate()->firstOrFail();

                if (! in_array($garage->type->name, Vehicle::compatibleGarageTypes($data['vehicle_type']), true)) {
                    $this->fail('garage_id', 'This garage type cannot be used for the selected vehicle.');
                }

                if ($garage->type->daily_price === null) {
                    $this->fail('garage_id', 'The price for this garage type has not been set yet.');
                }

                if (Rental::where('garage_id', $garage->id)->overlapping($start, $end)->exists()) {
                    $this->fail('garage_id', 'This garage is already rented during the selected dates.');
                }

                $vehicle = $this->resolveVehicle($renter, $data);

                if ($vehicle->plate_number !== null
                    && Rental::where('vehicle_id', $vehicle->id)->overlapping($start, $end)->exists()) {
                    $this->fail('plate_number', 'This vehicle already has a rental during the selected dates.');
                }

                $items = $this->pricing->priceItems($plan, $garage->type->daily_price);
                $charging = $this->chargingFor(
                    $data['vehicle_type'],
                    $garage->type,
                    $data['battery_capacity_kwh'] ?? null,
                    $data['remaining_battery_kwh'] ?? null
                );

                $storedPath = $proof->store('payment_proofs', 'public');

                $rental = Rental::create([
                    'user_id' => $renter->id,
                    'vehicle_id' => $vehicle->id,
                    'garage_id' => $garage->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'remaining_battery_kwh' => $vehicle->type === Vehicle::ELECTRIC_CAR
                        ? ($data['remaining_battery_kwh'] ?? null)
                        : null,
                    'charging_cost' => $charging,
                    'total_cost' => $this->pricing->subtotal($items) + $charging,
                    'payment_proof_path' => $storedPath,
                ]);

                foreach ($items as $item) {
                    $rental->items()->create($this->itemAttributes($item));
                }

                // Assignment rule: a saved rental marks the garage as used.
                $garage->update(['status' => Garage::STATUS_USED]);

                return $rental->load(['garage', 'vehicle', 'items']);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }
    }

    /**
     * Preview the extra cost of moving the end date of a rental.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException When the extension is not allowed.
     */
    public function quoteExtension(Rental $rental, string $newEndDate): array
    {
        $newEnd = CarbonImmutable::createFromFormat('Y-m-d', $newEndDate)->startOfDay();
        $result = $this->planExtension($rental->loadMissing('garage.type'), $newEnd);

        return [
            'rental_id' => $rental->id,
            'current_end_date' => $rental->end_date->toDateString(),
            'new_end_date' => $newEnd->toDateString(),
            'extra_days' => $result['extra_days'],
            'items' => $this->formatItems($result['items']),
            'additional_cost' => $result['cost'],
        ];
    }

    /**
     * Extend a rental to a later end date and record the extra payment.
     *
     * @throws ValidationException When the extension is not allowed.
     */
    public function extend(Rental $rental, string $newEndDate, UploadedFile $proof): RentalExtension
    {
        $newEnd = CarbonImmutable::createFromFormat('Y-m-d', $newEndDate)->startOfDay();
        $storedPath = null;

        try {
            return DB::transaction(function () use ($rental, $newEnd, $proof, &$storedPath): RentalExtension {
                $locked = Rental::with('garage.type')->whereKey($rental->id)->lockForUpdate()->firstOrFail();
                $result = $this->planExtension($locked, $newEnd);

                $storedPath = $proof->store('payment_proofs', 'public');

                $extension = RentalExtension::create([
                    'rental_id' => $locked->id,
                    'previous_end_date' => $locked->end_date->toDateString(),
                    'new_end_date' => $newEnd->toDateString(),
                    'additional_cost' => $result['cost'],
                    'payment_proof_path' => $storedPath,
                ]);

                foreach ($result['items'] as $item) {
                    $locked->items()->create($this->itemAttributes($item));
                }

                $locked->update([
                    'end_date' => $newEnd->toDateString(),
                    'total_cost' => $locked->total_cost + $result['cost'],
                ]);

                return $extension;
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }
    }

    /**
     * Validate an extension and price the extra days.
     *
     * Extra days start the day after the current end date and use the same
     * split rule as a new rental, so a full extra month is billed monthly.
     *
     * @return array{extra_days: int, items: array<int, array<string, mixed>>, cost: int}
     */
    private function planExtension(Rental $rental, CarbonImmutable $newEnd): array
    {
        if (! $rental->isActiveOn(CarbonImmutable::today())) {
            $this->fail('rental', 'Only rentals that are active today can be extended.');
        }

        $currentEnd = CarbonImmutable::instance($rental->end_date)->startOfDay();

        if ($newEnd->lte($currentEnd)) {
            $this->fail('new_end_date', 'The new end date must be after the current end date.');
        }

        $extraDays = (int) round(abs($currentEnd->diffInDays($newEnd)));

        if ($extraDays > RentalPlanner::MAX_DAYS) {
            $this->fail('new_end_date', 'An extension cannot be longer than ' . RentalPlanner::MAX_DAYS . ' days.');
        }

        $from = $currentEnd->addDay();

        $conflict = Rental::where('garage_id', $rental->garage_id)
            ->where('id', '!=', $rental->id)
            ->overlapping($from, $newEnd)
            ->exists();

        if ($conflict) {
            $this->fail('new_end_date', 'Another rental already uses this garage during the extension period.');
        }

        $plan = $this->planner->plan($from, RentalItem::DAILY, $extraDays);
        $items = $this->pricing->priceItems($plan, $rental->garage->type->daily_price);

        return [
            'extra_days' => $extraDays,
            'items' => $items,
            'cost' => $this->pricing->subtotal($items),
        ];
    }

    /**
     * Reuse the renter's vehicle by plate number, or create it. Bicycles
     * have no plate, so each bicycle rental stores a new vehicle row.
     *
     * @param array<string, mixed> $data
     */
    private function resolveVehicle(User $renter, array $data): Vehicle
    {
        $attributes = [
            'type' => $data['vehicle_type'],
            'brand' => $data['brand'],
            'model_name' => $data['model_name'],
            'battery_capacity_kwh' => $data['vehicle_type'] === Vehicle::ELECTRIC_CAR
                ? $data['battery_capacity_kwh']
                : null,
        ];

        if ($data['vehicle_type'] === Vehicle::BICYCLE) {
            return $renter->vehicles()->create($attributes + ['plate_number' => null]);
        }

        return $renter->vehicles()->updateOrCreate(['plate_number' => $data['plate_number']], $attributes);
    }

    /**
     * Charging cost applies only to an electric car in an electric car garage.
     */
    private function chargingFor(string $vehicleType, GarageType $garageType, mixed $capacity, mixed $remaining): int
    {
        $applies = $vehicleType === Vehicle::ELECTRIC_CAR
            && $garageType->name === GarageType::ELECTRIC_CAR
            && $capacity !== null
            && $remaining !== null
            && $garageType->electricity_price_per_kwh !== null;

        return $applies
            ? $this->pricing->chargingCost((float) $capacity, (float) $remaining, (int) $garageType->electricity_price_per_kwh)
            : 0;
    }

    /**
     * Parse the start date, defaulting to today.
     */
    private function resolveStartDate(?string $value): CarbonImmutable
    {
        return $value === null || $value === ''
            ? CarbonImmutable::today()
            : CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    /**
     * Convert a priced part into database attributes.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function itemAttributes(array $item): array
    {
        return [
            'rental_type' => $item['rental_type'],
            'start_date' => $item['start_date']->toDateString(),
            'end_date' => $item['end_date']->toDateString(),
            'days' => $item['days'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal'],
        ];
    }

    /**
     * Convert planned parts into JSON friendly arrays.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function formatItems(array $items): array
    {
        return array_map(fn (array $item): array => array_merge($item, [
            'start_date' => $item['start_date']->toDateString(),
            'end_date' => $item['end_date']->toDateString(),
        ]), $items);
    }

    /**
     * Throw a validation error for one field.
     *
     * @throws ValidationException
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}