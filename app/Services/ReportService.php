<?php

namespace App\Services;

use App\Models\Rental;
use Carbon\Carbon;

/**
 * Builds the rental report grouped per month or per day.
 */
class ReportService
{
    /**
     * Build the report for the given filters.
     *
     * Rentals are counted in the period of their start date. The total of a
     * rental includes the cost of its extensions.
     *
     * @param array<string, mixed> $filters group, year, month, garage_type_id, user_id
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $group = $filters['group'] ?? 'month';
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);

        $query = Rental::with(['user', 'vehicle', 'garage.type'])->whereYear('start_date', $year);

        if ($group === 'day') {
            $query->whereMonth('start_date', $month);
        }

        if (! empty($filters['garage_type_id'])) {
            $typeId = (int) $filters['garage_type_id'];
            $query->whereHas('garage', fn ($garage) => $garage->where('garage_type_id', $typeId));
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        $rentals = $query->orderBy('start_date')->orderBy('id')->get();

        $buckets = $group === 'month'
            ? range(1, 12)
            : range(1, Carbon::create($year, $month, 1)->daysInMonth);

        $summary = [];

        foreach ($buckets as $bucket) {
            $inBucket = $rentals->filter(
                fn (Rental $rental): bool => ($group === 'month' ? $rental->start_date->month : $rental->start_date->day) === $bucket
            );

            $summary[] = [
                'label' => $group === 'month'
                    ? Carbon::create($year, $bucket, 1)->format('F Y')
                    : sprintf('%04d-%02d-%02d', $year, $month, $bucket),
                'count' => $inBucket->count(),
                'total' => (int) $inBucket->sum('total_cost'),
            ];
        }

        return [
            'group' => $group,
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
            'rentals' => $rentals,
            'count' => $rentals->count(),
            'grand_total' => (int) $rentals->sum('total_cost'),
        ];
    }
}