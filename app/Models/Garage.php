<?php

namespace App\Models;

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
}