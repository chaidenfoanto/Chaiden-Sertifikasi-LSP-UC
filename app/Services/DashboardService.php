<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\GarageType;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Collects the numbers and short lists shown on the admin dashboard.
 */
class DashboardService
{
    /**
     * Maximum rows in each short list on the dashboard.
     */
    public const LIST_LIMIT = 5;

    /**
     * A rental that ends within this many days counts as ending soon.
     */
    public const ENDING_SOON_DAYS = 7;

    /**
     * Build the dashboard data as of today.
     *
     * Revenue follows the same rule as the report page. A rental is counted
     * in the month of its start date and its total includes extensions.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $today = CarbonImmutable::today();
        $garagesTotal = Garage::count();
        $occupiedToday = Garage::whereHas('rentals', fn ($query) => $query->activeOn($today))->count();

        $pendingRenters = fn () => User::where('role', User::ROLE_RENTER)
            ->where('status', User::STATUS_PENDING);

        return [
            'pending_renters' => $pendingRenters()->count(),
            'pending_list' => $pendingRenters()->orderBy('created_at')->limit(self::LIST_LIMIT)->get(),
            'active_rentals' => Rental::activeOn($today)->count(),
            'garages_total' => $garagesTotal,
            'garages_free_today' => $garagesTotal - $occupiedToday,
            'month_revenue' => (int) Rental::query()
                ->whereDate('start_date', '>=', $today->startOfMonth()->toDateString())
                ->whereDate('start_date', '<=', $today->endOfMonth()->toDateString())
                ->sum('total_cost'),
            'ending_soon' => Rental::activeOn($today)
                ->whereDate('end_date', '<=', $today->addDays(self::ENDING_SOON_DAYS)->toDateString())
                ->with(['user', 'garage.type'])
                ->orderBy('end_date')
                ->limit(self::LIST_LIMIT)
                ->get(),
            'types' => GarageType::withCount([
                'garages',
                'garages as occupied_count' => fn ($garage) => $garage
                    ->whereHas('rentals', fn ($rental) => $rental->activeOn($today)),
            ])->orderBy('code')->get(),
        ];
    }
}