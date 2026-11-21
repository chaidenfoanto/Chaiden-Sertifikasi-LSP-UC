<?php

namespace App\Http\Requests;

use App\Models\GarageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the batch garage creation form.
 */
class StoreGarageBatchRequest extends FormRequest
{
    /**
     * Route middleware already restricts this to admins.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for batch creation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'garage_type_id' => ['required', 'exists:garage_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'daily_price' => ['required', 'integer', 'min:1', 'max:1000000'],
            'electricity_price_per_kwh' => [
                Rule::requiredIf(fn () => $this->isElectricType()),
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'electricity_price_per_kwh.required' => 'The electricity price per kWh is required for electric car garages.',
        ];
    }

    /**
     * Determine whether the selected garage type is the electric car type.
     */
    private function isElectricType(): bool
    {
        $type = GarageType::find($this->input('garage_type_id'));

        return $type !== null && $type->name === GarageType::ELECTRIC_CAR;
    }
}