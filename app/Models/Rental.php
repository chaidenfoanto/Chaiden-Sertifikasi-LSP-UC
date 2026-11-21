<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One rental transaction of a garage for a date range (both ends inclusive).
 */
class Rental extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'garage_id',
        'start_date',
        'end_date',
        'remaining_battery_kwh',
        'charging_cost',
        'total_cost',
        'payment_proof_path',
    ];

    /**
     * Attribute casting rules.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'remaining_battery_kwh' => 'float',
            'charging_cost' => 'integer',
            'total_cost' => 'integer',
        ];
    }

    /**
     * The renter who made the rental.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The vehicle stored in the garage.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The rented garage.
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Daily and monthly parts that make up this rental.
     */
    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    /**
     * Extensions applied to this rental by an admin.
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    /**
     * Limit to rentals whose date range overlaps the given range.
     * whereDate keeps the comparison correct on MySQL and SQLite.
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());
    }

    /**
     * Limit to rentals that cover the given day.
     */
    public function scopeActiveOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->overlapping($date, $date);
    }

    /**
     * Determine whether the rental covers the given day.
     */
    public function isActiveOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return $this->start_date->toDateString() <= $day && $this->end_date->toDateString() >= $day;
    }
}