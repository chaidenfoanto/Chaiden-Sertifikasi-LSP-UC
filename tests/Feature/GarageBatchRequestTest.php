<?php

namespace Tests\Feature;

use App\Models\GarageType;
use App\Models\User;
use Database\Seeders\GarageTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Covers the admin batch garage creation form.
 */
class GarageBatchRequestTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GarageTypeSeeder::class);
        $this->admin = $this->makeUser(User::ROLE_ADMIN);
    }

    private function typeId(string $name): int
    {
        return (int) GarageType::where('name', $name)->value('id');
    }

    /**
     * Post the batch form as the admin.
     *
     * @param array<string, mixed> $data
     */
    private function create(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.garages.store'), $data);
    }

    /**
     * Quantities outside the allowed range of 1 to 999.
     *
     * @return array<string, array{0: int}>
     */
    public static function invalidQuantities(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'over 999' => [1000]];
    }

    public function test_admin_creates_a_batch_with_sequential_codes_and_sets_the_price(): void
    {
        $this->create([
            'garage_type_id' => $this->typeId(GarageType::BICYCLE),
            'quantity' => 3,
            'daily_price' => 5000,
        ])->assertRedirect(route('admin.garages.create'));

        $this->assertDatabaseHas('garages', ['code' => 'S001']);
        $this->assertDatabaseHas('garages', ['code' => 'S003']);
        $this->assertDatabaseCount('garages', 3);
        $this->assertDatabaseHas('garage_types', ['name' => GarageType::BICYCLE, 'daily_price' => 5000]);
    }

    public function test_a_second_batch_continues_the_numbering_of_its_type(): void
    {
        $typeId = $this->typeId(GarageType::CAR);

        $this->create(['garage_type_id' => $typeId, 'quantity' => 2, 'daily_price' => 25000]);
        $this->create(['garage_type_id' => $typeId, 'quantity' => 2, 'daily_price' => 25000]);

        $this->assertDatabaseHas('garages', ['code' => 'C004']);
        $this->assertDatabaseMissing('garages', ['code' => 'C005']);
    }

    #[DataProvider('invalidQuantities')]
    public function test_quantity_must_be_between_1_and_999(int $quantity): void
    {
        $this->create([
            'garage_type_id' => $this->typeId(GarageType::CAR),
            'quantity' => $quantity,
            'daily_price' => 25000,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('garages', 0);
    }

    public function test_daily_price_must_be_positive(): void
    {
        $this->create([
            'garage_type_id' => $this->typeId(GarageType::CAR),
            'quantity' => 1,
            'daily_price' => 0,
        ])->assertSessionHasErrors('daily_price');
    }

    public function test_electric_car_garages_require_an_electricity_price(): void
    {
        $this->create([
            'garage_type_id' => $this->typeId(GarageType::ELECTRIC_CAR),
            'quantity' => 1,
            'daily_price' => 35000,
        ])->assertSessionHasErrors('electricity_price_per_kwh');

        $this->assertDatabaseCount('garages', 0);
    }

    public function test_electric_car_batch_stores_the_electricity_price(): void
    {
        $this->create([
            'garage_type_id' => $this->typeId(GarageType::ELECTRIC_CAR),
            'quantity' => 1,
            'daily_price' => 35000,
            'electricity_price_per_kwh' => 2500,
        ])->assertRedirect(route('admin.garages.create'));

        $this->assertDatabaseHas('garages', ['code' => 'E001']);
        $this->assertDatabaseHas('garage_types', [
            'name' => GarageType::ELECTRIC_CAR,
            'electricity_price_per_kwh' => 2500,
        ]);
    }

    public function test_electricity_price_is_ignored_for_non_electric_types(): void
    {
        $this->create([
            'garage_type_id' => $this->typeId(GarageType::BICYCLE),
            'quantity' => 1,
            'daily_price' => 5000,
            'electricity_price_per_kwh' => 3000,
        ]);

        $this->assertNull(GarageType::where('name', GarageType::BICYCLE)->value('electricity_price_per_kwh'));
    }

    public function test_renter_cannot_create_garages(): void
    {
        $this->actingAs($this->makeUser())
            ->post(route('admin.garages.store'), [
                'garage_type_id' => $this->typeId(GarageType::CAR),
                'quantity' => 1,
                'daily_price' => 25000,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('garages', 0);
    }
}