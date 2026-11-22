<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\GarageType;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageService;
use Carbon\CarbonImmutable;
use Database\Seeders\GarageTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GarageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_are_generated_and_continue_per_type(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $service = new GarageService();
        $bicycle = GarageType::where('name', GarageType::BICYCLE)->first();
        $electric = GarageType::where('name', GarageType::ELECTRIC_CAR)->first();

        $this->assertSame(['S001', 'S002', 'S003'], $service->createBatch($bicycle, 3, 5000, null));
        $this->assertSame(['S004', 'S005'], $service->createBatch($bicycle, 2, 5000, null));
        $this->assertSame(['E001'], $service->createBatch($electric, 1, 100000, 2000));
    }

    public function test_new_batch_price_applies_to_the_whole_type(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $service = new GarageService();
        $car = GarageType::where('name', GarageType::CAR)->first();

        $service->createBatch($car, 2, 10000, null);
        $this->assertEquals(250000, $car->fresh()->monthly_price);

        $service->createBatch($car, 3, 15000, null);

        $car = $car->fresh();
        $this->assertEquals(15000, $car->daily_price);
        $this->assertEquals(375000, $car->monthly_price);
        $this->assertSame(5, $car->garages()->count());
    }

    public function test_batch_over_999_garages_is_rejected(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $service = new GarageService();
        $car = GarageType::where('name', GarageType::CAR)->first();

        $service->createBatch($car, 999, 100000, null);

        $this->expectException(ValidationException::class);
        $service->createBatch($car, 1, 100000, null);
    }

    public function test_sync_marks_garages_used_or_available_by_their_rentals(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $service = new GarageService();
        $car = GarageType::where('name', GarageType::CAR)->first();
        $service->createBatch($car, 3, 100000, null);

        $renter = User::create([
            'full_name' => 'Test Renter',
            'nik' => '7371000000000001',
            'phone_number' => '081234567890',
            'email' => 'renter@test.local',
            'password' => 'password123',
            'role' => User::ROLE_RENTER,
            'status' => User::STATUS_APPROVED,
        ]);
        $vehicle = Vehicle::create([
            'user_id' => $renter->id,
            'type' => Vehicle::CAR,
            'plate_number' => 'DD 1 AB',
            'brand' => 'Toyota',
            'model_name' => 'Avanza',
        ]);
        $today = CarbonImmutable::today();

        // C001 ended yesterday, C002 is running today, C003 has no rental.
        foreach (['C001' => [-5, -1], 'C002' => [-1, 3]] as $code => [$from, $to]) {
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
        Garage::query()->update(['status' => Garage::STATUS_USED]);

        $service->syncStatuses();

        $this->assertSame('available', Garage::where('code', 'C001')->value('status'));
        $this->assertSame('used', Garage::where('code', 'C002')->value('status'));
        $this->assertSame('available', Garage::where('code', 'C003')->value('status'));
    }
}