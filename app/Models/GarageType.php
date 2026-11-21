<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Garage type (bicycle, car, electric_car) holding the pricing per type.
 */
class GarageType extends Model
{
    public const BICYCLE = 'bicycle';
    public const CAR = 'car';
    public const ELECTRIC_CAR = 'electric_car';

    protected $fillable = [
        'name',
        'code',
        'daily_price',
        'electricity_price_per_kwh',
    ];

    /**
     * Garages that belong to this type.
     */
    public function garages(): HasMany
    {
        return $this->hasMany(Garage::class);
    }

    /**
     * Monthly price, always 25 times the daily price.
     */
    public function getMonthlyPriceAttribute(): ?int
    {
        return $this->daily_price === null ? null : $this->daily_price * 25;
    }
}