<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the filters of the rental report page.
 */
class ReportFilterRequest extends FormRequest
{
    /**
     * Route middleware already restricts this to admins.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for report filters. All of them are optional.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'group' => ['nullable', Rule::in(['month', 'day'])],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'garage_type_id' => ['nullable', 'exists:garage_types,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}