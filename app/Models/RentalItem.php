<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A daily or monthly part of a rental, created when a long rental is split.
 */
class RentalItem extends Model
{
    public const DAILY = 'daily';
    public const MONTHLY = 'monthly';

    protected $fillable = [
        'rental_id',
        'rental_type',
        'start_date',
        'end_date',
        'days',
        'unit_price',
        'subtotal',
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
            'days' => 'integer',
            'unit_price' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * The rental this item belongs to.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}