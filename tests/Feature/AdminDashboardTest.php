<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\GarageType;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DashboardService;
use App\Services\GarageService;
use Carbon\CarbonImmutable;
use Database\Seeders\GarageTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Covers the numbers and the access rules of the admin dashboard.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;

    /**
     * Create 3 car garages. C001 runs today and ends in 2 days,
     * C002 ended yesterday, and C003 has no rental.
     */
    private function seedRentals(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $car = GarageType::where('name', GarageType::CAR)->firstOrFail();
        app(GarageService::class)->createBatch($car, 3, 10000, null);

        $renter = $this->makeUser();
        $vehicle = Vehicle::create([
            'user_id' => $renter->id,
            'type' => Vehicle::CAR,
            'plate_number' => 'DD 1 AB',
            'brand' => 'Toyota',
            'model_name' => 'Avanza',
        ]);
        $today = CarbonImmutable::today();

        foreach (['C001' => [0, 2], 'C002' => [-5, -1]] as $code => [$from, $to]) {
            Rental::create([
                'user_id' => $renter->id,
                'vehicle_id' => $vehicle->id,
                'garage_id' => Garage::where('code', $code)->value('id'),
                'start_date' => $today->addDays($from)->toDateString(),
                'end_date' => $today->addDays($to)->toDateString(),
                'total_cost' => 100000,
                'payment_proof_path' => 'payment_proofs/test.jpg',
            ]);
        }
    }

    public function test_summary_counts_active_rentals_and_free_garages(): void
    {
        $this->seedRentals();

        $summary = app(DashboardService::class)->summary();

        $this->assertSame(1, $summary['active_rentals']);
        $this->assertSame(3, $summary['garages_total']);
        $this->assertSame(2, $summary['garages_free_today']);
    }

    public function test_summary_lists_the_rental_that_ends_soon(): void
    {
        $this->seedRentals();

        $summary = app(DashboardService::class)->summary();

        $this->assertCount(1, $summary['ending_soon']);
        $this->assertSame('C001', $summary['ending_soon']->first()->garage->code);
    }

    public function test_summary_counts_pending_renters_only(): void
    {
        $this->makeUser(User::ROLE_RENTER, User::STATUS_PENDING);
        $this->makeUser(User::ROLE_RENTER, User::STATUS_APPROVED);

        $summary = app(DashboardService::class)->summary();

        $this->assertSame(1, $summary['pending_renters']);
        $this->assertCount(1, $summary['pending_list']);
    }

    public function test_summary_shows_occupied_garages_per_type(): void
    {
        $this->seedRentals();

        $car = app(DashboardService::class)->summary()['types']->firstWhere('name', GarageType::CAR);

        $this->assertSame(3, $car->garages_count);
        $this->assertSame(1, $car->occupied_count);
    }

    public function test_month_revenue_includes_a_rental_that_starts_today(): void
    {
        $this->seedRentals();

        $this->assertGreaterThanOrEqual(100000, app(DashboardService::class)->summary()['month_revenue']);
    }

    public function test_admin_can_open_the_dashboard(): void
    {
        $this->seedRentals();

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending registrations')
            ->assertSee('Garage types today')
            ->assertSee('View website');
    }

    public function test_renter_cannot_open_the_admin_dashboard(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}