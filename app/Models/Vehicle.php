<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A vehicle owned by a renter (bicycle, motorcycle, car, or electric car).
 */
class Vehicle extends Model
{
    public const BICYCLE = 'bicycle';
    public const MOTORCYCLE = 'motorcycle';
    public const CAR = 'car';
    public const ELECTRIC_CAR = 'electric_car';

    public const TYPES = [self::BICYCLE, self::MOTORCYCLE, self::CAR, self::ELECTRIC_CAR];

    protected $fillable = [
        'user_id',
        'type',
        'plate_number',
        'brand',
        'model_name',
        'battery_capacity_kwh',
    ];

    /**
     * Attribute casting rules.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['battery_capacity_kwh' => 'float'];
    }

    /**
     * The renter who owns this vehicle.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Rentals made with this vehicle.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Garage type names that accept the given vehicle type.
     *
     * @return array<int, string>
     */
    public static function compatibleGarageTypes(string $vehicleType): array
    {
        return match ($vehicleType) {
            self::BICYCLE, self::MOTORCYCLE => [GarageType::BICYCLE],
            self::CAR => [GarageType::CAR],
            self::ELECTRIC_CAR => [GarageType::CAR, GarageType::ELECTRIC_CAR],
            default => [],
        };
    }
}