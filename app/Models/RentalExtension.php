<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Record of one end-date extension and its extra payment proof.
 */
class RentalExtension extends Model
{
    protected $fillable = [
        'rental_id',
        'previous_end_date',
        'new_end_date',
        'additional_cost',
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
            'previous_end_date' => 'date',
            'new_end_date' => 'date',
            'additional_cost' => 'integer',
        ];
    }

    /**
     * The extended rental.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}