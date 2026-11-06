<?php

namespace App\Http\Requests\V1;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Patient::class);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['female', 'male'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'dob_estimated' => ['sometimes', 'boolean'],
            'national_id' => ['nullable', 'string', 'max:50', Rule::unique('patient', 'national_id')],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
