<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}