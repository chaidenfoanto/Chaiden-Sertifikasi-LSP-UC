<?php

namespace App\Services;

use App\Models\RentalItem;

/**
 * Money calculations for rentals and electric car charging.
 */
class RentalPricing
{
    /**
     * A monthly rental costs 25 times the daily price.
     */
    public const MONTHLY_MULTIPLIER = 25;

    /**
     * Monthly price for a daily price.
     */
    public function monthlyPrice(int $dailyPrice): int
    {
        return $dailyPrice * self::MONTHLY_MULTIPLIER;
    }

    /**
     * Add unit_price and subtotal to every planned part.
     *
     * @param array<int, array<string, mixed>> $plan
     * @return array<int, array<string, mixed>>
     */
    public function priceItems(array $plan, int $dailyPrice): array
    {
        return array_map(function (array $item) use ($dailyPrice): array {
            $isMonthly = $item['rental_type'] === RentalItem::MONTHLY;
            $unitPrice = $isMonthly ? $this->monthlyPrice($dailyPrice) : $dailyPrice;
            $units = $isMonthly ? 1 : $item['days'];

            return $item + ['unit_price' => $unitPrice, 'subtotal' => $unitPrice * $units];
        }, $plan);
    }

    /**
     * Sum of all part subtotals.
     *
     * @param array<int, array{subtotal: int}> $pricedItems
     */
    public function subtotal(array $pricedItems): int
    {
        return (int) array_sum(array_column($pricedItems, 'subtotal'));
    }

    /**
     * Charging cost to fill the battery: (capacity - remaining) x price per kWh,
     * rounded to the nearest rupiah and never below zero.
     */
    public function chargingCost(float $capacityKwh, float $remainingKwh, int $pricePerKwh): int
    {
        $needed = max(0.0, $capacityKwh - $remainingKwh);

        return (int) round($needed * $pricePerKwh);
    }
}