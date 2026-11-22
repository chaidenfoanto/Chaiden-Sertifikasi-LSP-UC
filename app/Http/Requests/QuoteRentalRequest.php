<?php

namespace App\Http\Requests;

use App\Models\RentalItem;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
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
        return [
            'vehicle_type' => ['required', Rule::in(Vehicle::TYPES)],
            'rental_type' => ['required', Rule::in([RentalItem::DAILY, RentalItem::MONTHLY])],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            // A monthly rental ends on its own, so only a daily rental needs an end date.
            'end_date' => ['nullable', 'required_if:rental_type,daily', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'battery_capacity_kwh' => ['nullable', 'numeric', 'gt:0', 'max:500'],
            'remaining_battery_kwh' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    /**
     * Normalize the plate number, e.g. " dd  1234 ab " becomes "DD 1234 AB".
     */
    protected function prepareForValidation(): void
    {
        // The start date defaults to today when it is left empty.
        if (blank($this->input('start_date'))) {
            $this->merge(['start_date' => CarbonImmutable::today()->toDateString()]);
        }

        $plate = $this->input('plate_number');

        if (is_string($plate)) {
            $this->merge(['plate_number' => strtoupper(trim(preg_replace('/\s+/', ' ', $plate)))]);
        }
    }
}