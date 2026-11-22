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
     * Keep only the number after +62.
     *
     * The form shows +62 as a fixed prefix, but 0812..., 62812... and +62812...
     * are accepted too. Spaces, dashes, dots, and brackets are removed.
     */
    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s\-().]+/', '', (string) $this->input('phone_number'));

        $this->merge(['phone_number' => preg_replace('/^(\+62|62|0)/', '', $phone)]);
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
            'phone_number' => ['required', 'regex:/^8[0-9]{8,11}$/'],
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
            'phone_number.regex' => 'Enter the number after +62, starting with 8, with 9 to 12 digits.',
            'email.unique' => 'This email is already registered.',
        ];
    }
}