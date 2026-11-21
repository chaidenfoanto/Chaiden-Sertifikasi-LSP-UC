<?php

namespace App\Services;

use App\Models\RentalItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Date logic for rentals: limits, month length, and splitting long rentals.
 */
class RentalPlanner
{
    /**
     * Upper limits that keep a single rental request reasonable.
     */
    public const MAX_DAYS = 366;
    public const MAX_MONTHS = 12;

    /**
     * Maximum days of a daily rental: days in the start month minus 1.
     */
    public function maxDailyDays(CarbonInterface $startDate): int
    {
        return $startDate->daysInMonth - 1;
    }

    /**
     * Length of a monthly rental: days in the month of the start date.
     */
    public function monthlyDays(CarbonInterface $startDate): int
    {
        return $startDate->daysInMonth;
    }

    /**
     * Split a request into rental parts.
     *
     * Monthly requests produce one monthly part per month. Daily requests
     * take full months first (a daily rental may never reach the length of
     * its month) and keep the leftover days as one daily part.
     *
     * @return array<int, array{rental_type: string, start_date: CarbonImmutable, end_date: CarbonImmutable, days: int}>
     *
     * @throws InvalidArgumentException When the type or quantity is invalid.
     */
    public function plan(CarbonInterface $startDate, string $rentalType, int $quantity): array
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! in_array($rentalType, [RentalItem::DAILY, RentalItem::MONTHLY], true)) {
            throw new InvalidArgumentException('Unknown rental type.');
        }

        $cursor = CarbonImmutable::instance($startDate)->startOfDay();
        $items = [];

        if ($rentalType === RentalItem::MONTHLY) {
            for ($i = 0; $i < $quantity; $i++) {
                $item = $this->makeItem(RentalItem::MONTHLY, $cursor, $this->monthlyDays($cursor));
                $items[] = $item;
                $cursor = $item['end_date']->addDay();
            }

            return $items;
        }

        $remaining = $quantity;

        while ($remaining >= $this->monthlyDays($cursor)) {
            $item = $this->makeItem(RentalItem::MONTHLY, $cursor, $this->monthlyDays($cursor));
            $items[] = $item;
            $remaining -= $item['days'];
            $cursor = $item['end_date']->addDay();
        }

        if ($remaining > 0) {
            $items[] = $this->makeItem(RentalItem::DAILY, $cursor, $remaining);
        }

        return $items;
    }

    /**
     * The last day covered by a plan.
     *
     * @param array<int, array{end_date: CarbonImmutable}> $plan
     */
    public function endDate(array $plan): CarbonImmutable
    {
        return end($plan)['end_date'];
    }

    /**
     * Build one rental part starting on the given day.
     *
     * @return array{rental_type: string, start_date: CarbonImmutable, end_date: CarbonImmutable, days: int}
     */
    private function makeItem(string $type, CarbonImmutable $start, int $days): array
    {
        return [
            'rental_type' => $type,
            'start_date' => $start,
            'end_date' => $start->addDays($days - 1),
            'days' => $days,
        ];
    }
}