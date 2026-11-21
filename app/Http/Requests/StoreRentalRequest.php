<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Validation\Rule;

/**
 * Validates saving a new rental, including vehicle data and payment proof.
 */
class StoreRentalRequest extends QuoteRentalRequest
{
    /**
     * Validation rules for a new rental.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isElectric = fn (): bool => $this->input('vehicle_type') === Vehicle::ELECTRIC_CAR;
        $needsPlate = fn (): bool => $this->input('vehicle_type') !== Vehicle::BICYCLE;

        return array_merge(parent::rules(), [
            'garage_id' => ['required', 'exists:garages,id'],
            'plate_number' => [Rule::requiredIf($needsPlate), 'nullable', 'string', 'max:15', 'regex:/^[A-Z0-9 ]+$/'],
            'brand' => ['required', 'string', 'max:50'],
            'model_name' => ['required', 'string', 'max:50'],
            'battery_capacity_kwh' => [Rule::requiredIf($isElectric), 'nullable', 'numeric', 'gt:0', 'max:500'],
            'remaining_battery_kwh' => [
                Rule::requiredIf($isElectric),
                'nullable',
                'numeric',
                'gte:0',
                'lte:battery_capacity_kwh',
            ],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate_number.required' => 'The plate number is required for every vehicle except a bicycle.',
            'plate_number.regex' => 'The plate number may only contain letters, digits, and spaces.',
            'remaining_battery_kwh.lte' => 'The remaining battery cannot be larger than the battery capacity.',
            'payment_proof.mimes' => 'The payment proof must be a jpg, jpeg, or png image.',
            'payment_proof.max' => 'The payment proof may not be larger than 5 MB.',
        ];
    }
}