<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\GarageType;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageService;
use App\Services\ReportService;
use Database\Seeders\GarageTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the four report modes (month, day, garage type, renter).
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $plateCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GarageTypeSeeder::class);
        $garages = app(GarageService::class);
        $garages->createBatch(GarageType::where('name', GarageType::BICYCLE)->first(), 1, 5000, null);
        $garages->createBatch(GarageType::where('name', GarageType::CAR)->first(), 1, 25000, null);
    }

    private function makeRenter(string $name): User
    {
        return User::create([
            'full_name' => $name,
            'email' => strtolower($name) . '@test.local',
            'password' => 'password123',
            'role' => User::ROLE_RENTER,
            'status' => User::STATUS_APPROVED,
        ]);
    }

    private function makeRental(User $renter, string $garageCode, string $start, int $total): Rental
    {
        $vehicle = Vehicle::create([
            'user_id' => $renter->id,
            'type' => Vehicle::CAR,
            'plate_number' => 'DD ' . (++$this->plateCounter),
            'brand' => 'Toyota',
            'model_name' => 'Yaris',
        ]);

        return Rental::create([
            'user_id' => $renter->id,
            'vehicle_id' => $vehicle->id,
            'garage_id' => Garage::where('code', $garageCode)->value('id'),
            'start_date' => $start,
            'end_date' => $start,
            'charging_cost' => 0,
            'total_cost' => $total,
            'payment_proof_path' => 'payment_proofs/test.jpg',
        ]);
    }

    public function test_type_report_has_one_row_per_garage_type(): void
    {
        $year = now()->year;
        $alice = $this->makeRenter('Alice');
        $this->makeRental($alice, 'S001', "{$year}-03-10", 100000);
        $this->makeRental($alice, 'C001', "{$year}-04-10", 200000);
        $this->makeRental($alice, 'C001', "{$year}-05-10", 300000);

        $report = (new ReportService())->build(['group' => 'type', 'year' => $year]);
        $rows = collect($report['summary'])->keyBy('label');

        $this->assertCount(3, $report['summary']);
        $this->assertSame(1, $rows['Bicycle (S)']['count']);
        $this->assertSame(100000, $rows['Bicycle (S)']['total']);
        $this->assertSame(2, $rows['Car (C)']['count']);
        $this->assertSame(500000, $rows['Car (C)']['total']);
        $this->assertSame(0, $rows['Electric Car (E)']['count']);
        $this->assertSame(600000, $report['grand_total']);
    }

    public function test_renter_report_groups_by_renter_and_sorts_by_name(): void
    {
        $year = now()->year;
        $bob = $this->makeRenter('Bob');
        $alice = $this->makeRenter('Alice');
        $this->makeRental($bob, 'S001', "{$year}-03-10", 50000);
        $this->makeRental($alice, 'C001', "{$year}-03-11", 100000);
        $this->makeRental($alice, 'C001', "{$year}-03-12", 200000);

        $report = (new ReportService())->build(['group' => 'renter', 'year' => $year]);

        $this->assertSame(['Alice', 'Bob'], array_column($report['summary'], 'label'));
        $this->assertSame(300000, $report['summary'][0]['total']);
        $this->assertSame(2, $report['summary'][0]['count']);
        $this->assertSame(1, $report['summary'][1]['count']);
    }

    public function test_type_report_covers_all_years_when_year_is_empty(): void
    {
        $alice = $this->makeRenter('Alice');
        $this->makeRental($alice, 'S001', '2024-06-01', 100000);
        $this->makeRental($alice, 'S001', '2025-06-01', 150000);

        $report = (new ReportService())->build(['group' => 'type']);

        $this->assertNull($report['year']);
        $this->assertSame(2, $report['count']);
        $this->assertSame(250000, $report['grand_total']);
    }

    public function test_month_report_puts_a_rental_in_its_start_month(): void
    {
        $year = now()->year;
        $this->makeRental($this->makeRenter('Alice'), 'S001', "{$year}-03-10", 100000);

        $report = (new ReportService())->build(['group' => 'month', 'year' => $year]);

        $this->assertCount(12, $report['summary']);
        $this->assertSame("March {$year}", $report['summary'][2]['label']);
        $this->assertSame(1, $report['summary'][2]['count']);
        $this->assertSame(0, $report['summary'][0]['count']);
    }
}