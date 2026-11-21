<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the shared login form for admins and renters.
 */
class LoginRequest extends FormRequest
{
    /**
     * Anyone who is not logged in may try to log in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for login.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}