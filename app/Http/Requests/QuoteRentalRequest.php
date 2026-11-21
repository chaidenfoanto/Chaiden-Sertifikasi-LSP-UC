<?php

namespace App\Http\Requests;

use App\Models\RentalItem;
use App\Models\Vehicle;
use App\Services\RentalPlanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the input needed to preview a rental (dates, cost, free garages).
 */
class QuoteRentalRequest extends FormRequest
{
    /**
     * Route middleware already restricts this to renters.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the rental preview.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxQuantity = $this->input('rental_type') === RentalItem::MONTHLY
            ? RentalPlanner::MAX_MONTHS
            : RentalPlanner::MAX_DAYS;

        return [
            'vehicle_type' => ['required', Rule::in(Vehicle::TYPES)],
            'rental_type' => ['required', Rule::in([RentalItem::DAILY, RentalItem::MONTHLY])],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . $maxQuantity],
            'battery_capacity_kwh' => ['nullable', 'numeric', 'gt:0', 'max:500'],
            'remaining_battery_kwh' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    /**
     * Normalize the plate number, e.g. " dd  1234 ab " becomes "DD 1234 AB".
     */
    protected function prepareForValidation(): void
    {
        $plate = $this->input('plate_number');

        if (is_string($plate)) {
            $this->merge(['plate_number' => strtoupper(trim(preg_replace('/\s+/', ' ', $plate)))]);
        }
    }
}