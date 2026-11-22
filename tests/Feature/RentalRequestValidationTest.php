<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Covers the validation rules of the new rental form.
 *
 * The base input points to a garage that does not exist, so a request never
 * reaches the service. These tests only look at the field level rules.
 */
class RentalRequestValidationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesUsers;

    private User $renter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renter = $this->makeUser();
    }

    /**
     * Post the rental form with valid base input, then apply changes.
     *
     * @param array<string, mixed> $override
     * @param array<int, string> $remove Keys to leave out of the request.
     */
    private function submit(array $override = [], array $remove = []): TestResponse
    {
        $data = array_merge([
            'vehicle_type' => 'car',
            'rental_type' => 'daily',
            'quantity' => 3,
            'start_date' => now()->toDateString(),
            'garage_id' => 999999,
            'plate_number' => 'DD 1234 AB',
            'brand' => 'Toyota',
            'model_name' => 'Yaris',
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ], $override);

        foreach ($remove as $key) {
            unset($data[$key]);
        }

        return $this->actingAs($this->renter)->post(route('renter.rentals.store'), $data);
    }

    /**
     * Vehicle types that must come with a plate number.
     *
     * @return array<string, array{0: string}>
     */
    public static function vehicleTypesWithPlate(): array
    {
        return [
            'motorcycle' => ['motorcycle'],
            'car' => ['car'],
            'electric car' => ['electric_car'],
        ];
    }

    /**
     * Quantities that are one step over the limit.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function quantitiesOverTheLimit(): array
    {
        return [
            'zero days' => ['daily', 0],
            'daily over 366' => ['daily', 367],
            'zero months' => ['monthly', 0],
            'monthly over 12' => ['monthly', 13],
        ];
    }

    /**
     * Quantities that sit exactly on the limit and must be accepted.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function quantitiesOnTheLimit(): array
    {
        return [
            'one day' => ['daily', 1],
            'daily 366' => ['daily', 366],
            'one month' => ['monthly', 1],
            'monthly 12' => ['monthly', 12],
        ];
    }

    #[DataProvider('vehicleTypesWithPlate')]
    public function test_plate_number_is_required_except_for_a_bicycle(string $type): void
    {
        $this->submit(['vehicle_type' => $type], ['plate_number'])
            ->assertSessionHasErrors([
                'plate_number' => 'The plate number is required for every vehicle except a bicycle.',
            ]);
    }

    public function test_bicycle_does_not_need_a_plate_number(): void
    {
        $this->submit(['vehicle_type' => 'bicycle'], ['plate_number'])
            ->assertSessionDoesntHaveErrors('plate_number');
    }

    public function test_plate_number_is_normalized_before_validation(): void
    {
        $this->submit(['plate_number' => '  dd   1234  ab '])
            ->assertSessionDoesntHaveErrors('plate_number');
    }

    public function test_plate_number_with_symbols_is_rejected(): void
    {
        $this->submit(['plate_number' => 'DD-1234!'])
            ->assertSessionHasErrors('plate_number');
    }

    public function test_electric_car_requires_battery_capacity_and_remaining_charge(): void
    {
        $this->submit(['vehicle_type' => 'electric_car'])
            ->assertSessionHasErrors(['battery_capacity_kwh', 'remaining_battery_kwh']);
    }

    public function test_remaining_battery_cannot_exceed_the_capacity(): void
    {
        $this->submit([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => 40,
            'remaining_battery_kwh' => 50,
        ])->assertSessionHasErrors(['remaining_battery_kwh']);
    }

    public function test_petrol_car_does_not_need_battery_data(): void
    {
        $this->submit(['vehicle_type' => 'car'])
            ->assertSessionDoesntHaveErrors(['battery_capacity_kwh', 'remaining_battery_kwh']);
    }

    public function test_unknown_vehicle_or_rental_type_is_rejected(): void
    {
        $this->submit(['vehicle_type' => 'truck', 'rental_type' => 'weekly'])
            ->assertSessionHasErrors(['vehicle_type', 'rental_type']);
    }

    #[DataProvider('quantitiesOverTheLimit')]
    public function test_quantity_over_the_limit_is_rejected(string $type, int $quantity): void
    {
        $this->submit(['rental_type' => $type, 'quantity' => $quantity])
            ->assertSessionHasErrors('quantity');
    }

    #[DataProvider('quantitiesOnTheLimit')]
    public function test_quantity_on_the_limit_is_accepted(string $type, int $quantity): void
    {
        $this->submit(['rental_type' => $type, 'quantity' => $quantity])
            ->assertSessionDoesntHaveErrors('quantity');
    }

    public function test_payment_proof_is_required(): void
    {
        $this->submit([], ['payment_proof'])->assertSessionHasErrors('payment_proof');
    }

    public function test_payment_proof_must_be_an_image(): void
    {
        $pdf = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        $this->submit(['payment_proof' => $pdf])->assertSessionHasErrors('payment_proof');
    }

    public function test_payment_proof_must_be_jpg_jpeg_or_png(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->image('proof.gif')])
            ->assertSessionHasErrors(['payment_proof' => 'The payment proof must be a jpg, jpeg, or png image.']);
    }

    public function test_payment_proof_of_exactly_5_mb_is_accepted(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->image('proof.png')->size(5120)])
            ->assertSessionDoesntHaveErrors('payment_proof');
    }

    public function test_payment_proof_over_5_mb_is_rejected(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->image('proof.png')->size(5121)])
            ->assertSessionHasErrors(['payment_proof' => 'The payment proof may not be larger than 5 MB.']);
    }

    public function test_start_date_in_the_past_is_rejected(): void
    {
        $this->submit(['start_date' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('start_date');
    }

    public function test_start_date_of_today_is_accepted(): void
    {
        $this->submit(['start_date' => now()->toDateString()])
            ->assertSessionDoesntHaveErrors('start_date');
    }

    public function test_start_date_in_the_future_is_accepted(): void
    {
        $this->submit(['start_date' => now()->addWeek()->toDateString()])
            ->assertSessionDoesntHaveErrors('start_date');
    }

    public function test_start_date_must_use_the_year_month_day_format(): void
    {
        $this->submit(['start_date' => '25/11/2026'])
            ->assertSessionHasErrors('start_date');
    }
}