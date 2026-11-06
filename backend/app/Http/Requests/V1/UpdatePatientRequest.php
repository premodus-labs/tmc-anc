<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient'));
    }

    /**
     * Partial updates: only the fields sent are validated and changed.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'sex' => ['sometimes', 'required', Rule::in(['female', 'male'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'dob_estimated' => ['sometimes', 'boolean'],
            'national_id' => [
                'sometimes', 'nullable', 'string', 'max:50',
                Rule::unique('patient', 'national_id')->ignore($this->route('patient')),
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
