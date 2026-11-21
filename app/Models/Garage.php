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
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'garage_type_id',
        'sequence_number',
        'code',
        'status',
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
     * Determine whether the garage is rented on the given day.
     * This reads the rentals table, not the status column.
     */
    public function isUsedOn(CarbonInterface $date): bool
    {
        return $this->rentals()->activeOn($date)->exists();
    }
}