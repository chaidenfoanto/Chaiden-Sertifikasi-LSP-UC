<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\GarageType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for creating garages in batches.
 */
class GarageService
{
    /**
     * Maximum number of garages per type (codes run from 001 to 999).
     */
    public const MAX_PER_TYPE = 999;

    /**
     * Create a batch of garages for a type and update the type prices.
     *
     * Sequence numbers continue from the highest existing number of the type.
     * The type row is locked so two admins cannot generate the same codes.
     *
     * @return array<int, string> The generated garage codes, in order.
     *
     * @throws ValidationException When the 999 garage limit would be exceeded.
     */
    public function createBatch(
        GarageType $type,
        int $quantity,
        int $dailyPrice,
        ?int $electricityPrice
    ): array {
        return DB::transaction(function () use ($type, $quantity, $dailyPrice, $electricityPrice) {
            $lockedType = GarageType::whereKey($type->id)->lockForUpdate()->firstOrFail();
            $lastSequence = (int) Garage::where('garage_type_id', $lockedType->id)->max('sequence_number');

            if ($lastSequence + $quantity > self::MAX_PER_TYPE) {
                $remaining = max(0, self::MAX_PER_TYPE - $lastSequence);

                throw ValidationException::withMessages([
                    'quantity' => "Only {$remaining} more garage(s) can be created for this type (maximum "
                        . self::MAX_PER_TYPE . ').',
                ]);
            }

            $now = now();
            $rows = [];
            $codes = [];

            for ($i = 1; $i <= $quantity; $i++) {
                $sequence = $lastSequence + $i;
                $code = $this->buildCode($lockedType->code, $sequence);

                $rows[] = [
                    'garage_type_id' => $lockedType->id,
                    'sequence_number' => $sequence,
                    'code' => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $codes[] = $code;
            }

            Garage::insert($rows);

            $lockedType->update([
                'daily_price' => $dailyPrice,
                'electricity_price_per_kwh' => $lockedType->name === GarageType::ELECTRIC_CAR
                    ? $electricityPrice
                    : null,
            ]);

            return $codes;
        });
    }

    /**
     * Build a garage code from a type letter and a sequence number, e.g. S001.
     */
    public function buildCode(string $typeCode, int $sequence): string
    {
        return $typeCode . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}