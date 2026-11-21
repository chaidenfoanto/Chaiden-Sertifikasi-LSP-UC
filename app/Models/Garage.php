<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single rentable garage with a generated code such as S001.
 */
class Garage extends Model
{
    protected $fillable = [
        'garage_type_id',
        'sequence_number',
        'code',
    ];

    /**
     * The type of this garage.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(GarageType::class, 'garage_type_id');
    }

    /**
     * Rentals of this garage.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Determine whether the garage is "used" on the given day.
     */
    public function isUsedOn(CarbonInterface $date): bool
    {
        return $this->rentals()->activeOn($date)->exists();
    }
}