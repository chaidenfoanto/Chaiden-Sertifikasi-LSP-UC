<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an admin rental extension (new end date and extra payment proof).
 */
class ExtendRentalRequest extends FormRequest
{
    /**
     * Route middleware already restricts this to admins.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for an extension.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'new_end_date' => ['required', 'date_format:Y-m-d'],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
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
            'payment_proof.mimes' => 'The payment proof must be a jpg, jpeg, or png image.',
            'payment_proof.max' => 'The payment proof may not be larger than 5 MB.',
        ];
    }
}