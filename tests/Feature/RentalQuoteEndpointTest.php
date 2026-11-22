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
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Covers the JSON endpoints behind the live cost preview of the rental form
 * and the extension form: access rules, garage filtering, prices, and errors.
 */
class RentalQuoteEndpointTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;

    private User $renter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GarageTypeSeeder::class);
        $this->makeGarages(GarageType::CAR, 2, 100000);
        $this->makeGarages(GarageType::ELECTRIC_CAR, 1, 150000, 2000);
        $this->renter = $this->makeUser();
    }

    private function makeGarages(string $typeName, int $quantity, int $dailyPrice, ?int $kwhPrice = null): void
    {
        $type = GarageType::where('name', $typeName)->firstOrFail();
        app(GarageService::class)->createBatch($type, $quantity, $dailyPrice, $kwhPrice);
    }

    private function day(int $offset): string
    {
        return CarbonImmutable::today()->addDays($offset)->toDateString();
    }

    /**
     * Query of a three day car rental starting tomorrow. A null override removes the field.
     *
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function params(array $override = []): array
    {
        return array_filter(array_merge([
            'vehicle_type' => 'car',
            'rental_type' => 'daily',
            'start_date' => $this->day(1),
            'end_date' => $this->day(3),
        ], $override), fn ($value) => $value !== null);
    }

    /**
     * Call the renter quote endpoint as the logged in renter.
     *
     * @param array<string, mixed> $override
     */
    private function quote(array $override = []): TestResponse
    {
        return $this->actingAs($this->renter)
            ->getJson(route('renter.rentals.quote', $this->params($override)));
    }

    /**
     * Store a car rental of the renter on a garage, from and to are day offsets from today.
     */
    private function makeRental(string $garageCode, int $from, int $to): Rental
    {
        $vehicle = Vehicle::create([
            'user_id' => $this->renter->id,
            'type' => Vehicle::CAR,
            'plate_number' => 'DD 1 AB',
            'brand' => 'Toyota',
            'model_name' => 'Avanza',
        ]);

        return Rental::create([
            'user_id' => $this->renter->id,
            'vehicle_id' => $vehicle->id,
            'garage_id' => Garage::where('code', $garageCode)->value('id'),
            'start_date' => $this->day($from),
            'end_date' => $this->day($to),
            'total_cost' => 100000,
            'payment_proof_path' => 'payment_proofs/test.jpg',
        ]);
    }

    public function test_guest_cannot_use_the_renter_quote(): void
    {
        $this->getJson(route('renter.rentals.quote', $this->params()))
            ->assertUnauthorized();
    }

    public function test_admin_cannot_use_the_renter_quote(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->getJson(route('renter.rentals.quote', $this->params()))
            ->assertForbidden();
    }

    public function test_car_quote_lists_car_garages_with_the_calculated_total(): void
    {
        $this->quote()
            ->assertOk()
            ->assertJsonPath('start_date', $this->day(1))
            ->assertJsonPath('end_date', $this->day(3))
            ->assertJsonCount(2, 'garages')
            ->assertJsonPath('garages.0.code', 'C001')
            ->assertJsonPath('garages.0.subtotal', 300000)
            ->assertJsonPath('garages.0.charging_cost', 0)
            ->assertJsonPath('garages.0.total', 300000);
    }

    public function test_motorcycle_quote_lists_only_bicycle_garages(): void
    {
        $this->makeGarages(GarageType::BICYCLE, 1, 10000);

        $this->quote(['vehicle_type' => 'motorcycle'])
            ->assertOk()
            ->assertJsonCount(1, 'garages')
            ->assertJsonPath('garages.0.code', 'S001')
            ->assertJsonPath('garages.0.total', 30000);
    }

    public function test_electric_car_quote_charges_electricity_only_in_the_electric_garage(): void
    {
        // 40 kWh capacity with 10 kWh left needs 30 kWh, which costs 30 x 2000 = 60000.
        $this->quote([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => 40,
            'remaining_battery_kwh' => 10,
        ])
            ->assertOk()
            ->assertJsonCount(3, 'garages')
            ->assertJsonPath('garages.0.code', 'C001')
            ->assertJsonPath('garages.0.charging_cost', 0)
            ->assertJsonPath('garages.0.total', 300000)
            ->assertJsonPath('garages.2.code', 'E001')
            ->assertJsonPath('garages.2.charging_cost', 60000)
            ->assertJsonPath('garages.2.total', 510000);
    }

    public function test_monthly_quote_needs_no_end_date_and_costs_25_times_the_daily_price(): void
    {
        $start = CarbonImmutable::today()->startOfMonth()->addMonth();

        $this->quote([
            'rental_type' => 'monthly',
            'start_date' => $start->toDateString(),
            'end_date' => null,
        ])
            ->assertOk()
            ->assertJsonPath('end_date', $start->endOfMonth()->toDateString())
            ->assertJsonPath('items.0.rental_type', 'monthly')
            ->assertJsonPath('garages.0.total', 2500000);
    }

    public function test_daily_quote_is_limited_to_the_days_of_the_start_month_minus_one(): void
    {
        $start = CarbonImmutable::today()->startOfMonth()->addMonth();
        $limit = $start->daysInMonth - 1;

        $this->quote([
            'start_date' => $start->toDateString(),
            'end_date' => $start->addDays($limit - 1)->toDateString(),
        ])->assertOk();

        $this->quote([
            'start_date' => $start->toDateString(),
            'end_date' => $start->addDays($limit)->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_quote_rejects_a_start_date_in_the_past(): void
    {
        $this->quote(['start_date' => $this->day(-1), 'end_date' => $this->day(1)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_daily_quote_requires_an_end_date(): void
    {
        $this->quote(['end_date' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_quote_hides_a_garage_rented_during_the_dates(): void
    {
        $this->makeRental('C001', 2, 4);

        $this->quote()
            ->assertOk()
            ->assertJsonCount(1, 'garages')
            ->assertJsonPath('garages.0.code', 'C002');
    }

    public function test_quote_lists_a_garage_whose_rental_ended_the_day_before(): void
    {
        $this->makeRental('C001', -3, 0);

        $this->quote()
            ->assertOk()
            ->assertJsonCount(2, 'garages')
            ->assertJsonPath('garages.0.code', 'C001');
    }

    public function test_quote_hides_garages_of_a_type_without_a_price(): void
    {
        Garage::create([
            'garage_type_id' => GarageType::where('name', GarageType::BICYCLE)->value('id'),
            'sequence_number' => 1,
            'code' => 'S001',
        ]);

        $this->quote(['vehicle_type' => 'bicycle'])
            ->assertOk()
            ->assertJsonCount(0, 'garages');
    }

    public function test_extension_quote_prices_the_extra_days(): void
    {
        $rental = $this->makeRental('C001', 0, 2);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->getJson(route('admin.rentals.quote', ['rental' => $rental->id, 'new_end_date' => $this->day(5)]))
            ->assertOk()
            ->assertJsonPath('rental_id', $rental->id)
            ->assertJsonPath('current_end_date', $this->day(2))
            ->assertJsonPath('new_end_date', $this->day(5))
            ->assertJsonPath('extra_days', 3)
            ->assertJsonPath('additional_cost', 300000);
    }

    public function test_extension_quote_rejects_an_end_date_that_is_not_later(): void
    {
        $rental = $this->makeRental('C001', 0, 2);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->getJson(route('admin.rentals.quote', ['rental' => $rental->id, 'new_end_date' => $this->day(2)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_end_date']);
    }

    public function test_extension_quote_rejects_a_rental_that_is_not_active_today(): void
    {
        $rental = $this->makeRental('C001', -5, -1);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))
            ->getJson(route('admin.rentals.quote', ['rental' => $rental->id, 'new_end_date' => $this->day(3)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rental']);
    }

    public function test_renter_cannot_use_the_extension_quote(): void
    {
        $rental = $this->makeRental('C001', 0, 2);

        $this->actingAs($this->renter)
            ->getJson(route('admin.rentals.quote', ['rental' => $rental->id, 'new_end_date' => $this->day(5)]))
            ->assertForbidden();
    }
}