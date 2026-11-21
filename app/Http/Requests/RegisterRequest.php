<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the renter self-registration form.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Anyone who is not logged in may register.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for registration.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'phone_number' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'nik.digits' => 'The NIK must be exactly 16 digits.',
            'nik.unique' => 'This NIK is already registered.',
            'phone_number.regex' => 'The phone number must contain 8 to 15 digits.',
            'email.unique' => 'This email is already registered.',
        ];
    }
}