<?php

namespace Tests\Feature;

use App\Models\GarageType;
use App\Services\GarageService;
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

    public function test_batch_over_999_garages_is_rejected(): void
    {
        $this->seed(GarageTypeSeeder::class);
        $service = new GarageService();
        $car = GarageType::where('name', GarageType::CAR)->first();

        $service->createBatch($car, 999, 100000, null);

        $this->expectException(ValidationException::class);
        $service->createBatch($car, 1, 100000, null);
    }
}