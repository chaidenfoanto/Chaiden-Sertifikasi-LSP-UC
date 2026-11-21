<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\GarageType;
use App\Models\User;
use App\Services\GarageService;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Database\Seeders\GarageTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentalServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalService $service;
    private User $renter;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(GarageTypeSeeder::class);
        $this->service = app(RentalService::class);
        $this->renter = User::create([
            'full_name' => 'Test Renter',
            'nik' => '7371000000000001',
            'phone_number' => '081234567890',
            'email' => 'renter@test.local',
            'password' => 'password123',
            'role' => User::ROLE_RENTER,
            'status' => User::STATUS_APPROVED,
        ]);
    }

    private function makeGarages(string $typeName, int $quantity, int $dailyPrice, ?int $kwhPrice = null): void
    {
        $type = GarageType::where('name', $typeName)->first();
        app(GarageService::class)->createBatch($type, $quantity, $dailyPrice, $kwhPrice);
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->image('proof.jpg');
    }

    private function day(int $offset): string
    {
        return CarbonImmutable::today()->addDays($offset)->toDateString();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(string $garageCode, array $override = []): array
    {
        return array_merge([
            'vehicle_type' => 'car',
            'rental_type' => 'daily',
            'quantity' => 3,
            'start_date' => $this->day(1),
            'garage_id' => Garage::where('code', $garageCode)->value('id'),
            'plate_number' => 'DD 1234 AB',
            'brand' => 'Toyota',
            'model_name' => 'Avanza',
        ], $override);
    }

    public function test_car_rental_is_saved_with_calculated_total(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);

        $rental = $this->service->create($this->renter, $this->payload('C001'), $this->proof());

        $this->assertSame(300000, $rental->total_cost);
        $this->assertSame(0, $rental->charging_cost);
        $this->assertCount(1, $rental->items);
        Storage::disk('public')->assertExists($rental->payment_proof_path);
    }

    public function test_electric_car_in_electric_garage_pays_charging_cost(): void
    {
        $this->makeGarages(GarageType::ELECTRIC_CAR, 1, 150000, 2000);

        $rental = $this->service->create($this->renter, $this->payload('E001', [
            'vehicle_type' => 'electric_car',
            'quantity' => 2,
            'battery_capacity_kwh' => 60,
            'remaining_battery_kwh' => 20,
        ]), $this->proof());

        $this->assertSame(80000, $rental->charging_cost);
        $this->assertSame(380000, $rental->total_cost);
    }

    public function test_electric_car_in_regular_car_garage_has_no_charging_cost(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);

        $rental = $this->service->create($this->renter, $this->payload('C001', [
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => 60,
            'remaining_battery_kwh' => 20,
        ]), $this->proof());

        $this->assertSame(0, $rental->charging_cost);
        $this->assertSame(300000, $rental->total_cost);
    }

    public function test_bicycle_cannot_use_a_car_garage(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);

        $this->expectException(ValidationException::class);
        $this->service->create($this->renter, $this->payload('C001', ['vehicle_type' => 'bicycle']), $this->proof());
    }

    public function test_overlapping_rental_of_the_same_garage_is_rejected(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);
        $this->service->create($this->renter, $this->payload('C001'), $this->proof());

        $this->expectException(ValidationException::class);
        $this->service->create($this->renter, $this->payload('C001', [
            'plate_number' => 'DD 9999 ZZ',
            'start_date' => $this->day(3),
        ]), $this->proof());
    }

    public function test_rental_starting_right_after_another_is_allowed(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);
        $this->service->create($this->renter, $this->payload('C001'), $this->proof());

        $second = $this->service->create($this->renter, $this->payload('C001', [
            'plate_number' => 'DD 9999 ZZ',
            'start_date' => $this->day(4),
        ]), $this->proof());

        $this->assertSame($this->day(4), $second->start_date->toDateString());
    }

    public function test_extension_charges_only_the_extra_days(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);
        $rental = $this->service->create($this->renter, $this->payload('C001', [
            'start_date' => $this->day(0),
        ]), $this->proof());

        $extension = $this->service->extend($rental, $this->day(5), $this->proof());

        $this->assertSame(300000, $extension->additional_cost);
        $this->assertSame($this->day(5), $rental->fresh()->end_date->toDateString());
        $this->assertSame(600000, $rental->fresh()->total_cost);
    }

    public function test_extension_is_blocked_by_the_next_rental_of_the_garage(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);
        $first = $this->service->create($this->renter, $this->payload('C001', [
            'start_date' => $this->day(0),
        ]), $this->proof());
        $this->service->create($this->renter, $this->payload('C001', [
            'plate_number' => 'DD 9999 ZZ',
            'start_date' => $this->day(4),
            'quantity' => 2,
        ]), $this->proof());

        $this->expectException(ValidationException::class);
        $this->service->extend($first, $this->day(4), $this->proof());
    }

    public function test_only_rentals_active_today_can_be_extended(): void
    {
        $this->makeGarages(GarageType::CAR, 1, 100000);
        $future = $this->service->create($this->renter, $this->payload('C001'), $this->proof());

        $this->expectException(ValidationException::class);
        $this->service->extend($future, $this->day(8), $this->proof());
    }
}