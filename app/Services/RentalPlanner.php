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

        while ($remaining > $this->maxDailyDays($cursor)) {
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
     * Build the single date range of a new rental.
     *
     * A monthly rental lasts as many days as its start month has. A daily
     * rental runs from the start date to the given end date and may last at
     * most the days of its start month minus 1. A longer stay is made by
     * saving another rental that starts the day after this one ends.
     *
     * @return array<int, array{rental_type: string, start_date: CarbonImmutable, end_date: CarbonImmutable, days: int}>
     *
     * @throws InvalidArgumentException When the type or the dates are invalid.
     */
    public function planSingle(CarbonInterface $startDate, string $rentalType, ?CarbonInterface $endDate): array
    {
        $start = CarbonImmutable::instance($startDate)->startOfDay();

        if ($rentalType === RentalItem::MONTHLY) {
            return [$this->makeItem(RentalItem::MONTHLY, $start, $this->monthlyDays($start))];
        }

        if ($rentalType !== RentalItem::DAILY) {
            throw new InvalidArgumentException('Unknown rental type.');
        }

        if ($endDate === null) {
            throw new InvalidArgumentException('An end date is required for a daily rental.');
        }

        $end = CarbonImmutable::instance($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException('The end date cannot be before the start date.');
        }

        $days = (int) round($start->diffInDays($end)) + 1;
        $maxDays = $this->maxDailyDays($start);

        if ($days > $maxDays) {
            throw new InvalidArgumentException(
                "A daily rental starting on this date can last at most {$maxDays} days. "
                . 'For a longer stay, save this rental and make another one that starts the day after it ends.'
            );
        }

        return [$this->makeItem(RentalItem::DAILY, $start, $days)];
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