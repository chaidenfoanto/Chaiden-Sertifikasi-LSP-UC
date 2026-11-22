<?php

namespace App\Services;

use App\Models\GarageType;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the rental report grouped per month, day, garage type, or renter.
 */
class ReportService
{
    /**
     * Supported report modes.
     */
    public const GROUPS = ['month', 'day', 'type', 'renter'];

    /**
     * Build the report for the given filters.
     *
     * Rentals are counted in the period of their start date. The total of a
     * rental includes the cost of its extensions. Month and day reports
     * always use a year (default is the current one), while type and renter
     * reports cover all years when no year is given.
     *
     * @param array<string, mixed> $filters group, year, month, garage_type_id, user_id
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $group = $filters['group'] ?? 'month';
        $isPeriodGroup = in_array($group, ['month', 'day'], true);

        $year = isset($filters['year'])
            ? (int) $filters['year']
            : ($isPeriodGroup ? now()->year : null);
        $month = (int) ($filters['month'] ?? now()->month);

        $query = Rental::with(['user', 'vehicle', 'garage.type', 'extensions']);

        if ($year !== null) {
            $query->whereYear('start_date', $year);
        }

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

        return [
            'group' => $group,
            'year' => $year,
            'month' => $month,
            'summary' => $this->summarize($rentals, $group, $year, $month),
            'rentals' => $rentals,
            'count' => $rentals->count(),
            'grand_total' => (int) $rentals->sum('total_cost'),
        ];
    }

    /**
     * Pick the summary builder that matches the report mode.
     *
     * @param Collection<int, Rental> $rentals
     * @return array<int, array{label: string, count: int, total: int}>
     */
    private function summarize(Collection $rentals, string $group, ?int $year, int $month): array
    {
        return match ($group) {
            'day' => $this->summarizeByDay($rentals, (int) $year, $month),
            'type' => $this->summarizeByType($rentals),
            'renter' => $this->summarizeByRenter($rentals),
            default => $this->summarizeByMonth($rentals, (int) $year),
        };
    }

    /**
     * One row for each of the 12 months of the year.
     *
     * @param Collection<int, Rental> $rentals
     * @return array<int, array{label: string, count: int, total: int}>
     */
    private function summarizeByMonth(Collection $rentals, int $year): array
    {
        $rows = [];

        foreach (range(1, 12) as $month) {
            $rows[] = $this->row(
                Carbon::create($year, $month, 1)->format('F Y'),
                $rentals->filter(fn (Rental $rental): bool => $rental->start_date->month === $month)
            );
        }

        return $rows;
    }

    /**
     * One row for each day of the selected month.
     *
     * @param Collection<int, Rental> $rentals
     * @return array<int, array{label: string, count: int, total: int}>
     */
    private function summarizeByDay(Collection $rentals, int $year, int $month): array
    {
        $rows = [];

        foreach (range(1, Carbon::create($year, $month, 1)->daysInMonth) as $day) {
            $rows[] = $this->row(
                sprintf('%04d-%02d-%02d', $year, $month, $day),
                $rentals->filter(fn (Rental $rental): bool => $rental->start_date->day === $day)
            );
        }

        return $rows;
    }

    /**
     * One row for every garage type, including types without rentals.
     *
     * @param Collection<int, Rental> $rentals
     * @return array<int, array{label: string, count: int, total: int}>
     */
    private function summarizeByType(Collection $rentals): array
    {
        $rows = [];

        foreach (GarageType::orderBy('code')->get() as $type) {
            $rows[] = $this->row(
                ucwords(str_replace('_', ' ', $type->name)) . ' (' . $type->code . ')',
                $rentals->filter(fn (Rental $rental): bool => $rental->garage->garage_type_id === $type->id)
            );
        }

        return $rows;
    }

    /**
     * One row for every renter who has rentals, sorted by name.
     *
     * @param Collection<int, Rental> $rentals
     * @return array<int, array{label: string, count: int, total: int}>
     */
    private function summarizeByRenter(Collection $rentals): array
    {
        return $rentals
            ->groupBy('user_id')
            ->map(fn (Collection $items): array => $this->row($items->first()->user->full_name, $items))
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * Build one summary row from a set of rentals.
     *
     * @param Collection<int, Rental> $items
     * @return array{label: string, count: int, total: int}
     */
    private function row(string $label, Collection $items): array
    {
        return [
            'label' => $label,
            'count' => $items->count(),
            'total' => (int) $items->sum('total_cost'),
        ];
    }
}