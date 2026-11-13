<?php

namespace App\Http\Requests\V1;

use App\Models\Visitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Visitation::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patient', 'id')],
            'visit_type' => ['required', Rule::in(Visitation::VISIT_TYPES)],
            'priority' => ['sometimes', Rule::in(Visitation::PRIORITIES)],
            'patient_consent' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array{patient_id: int, visit_type: string, priority: string, patient_consent: bool}
     */
    public function visitData(): array
    {
        return ['priority' => 'routine', ...$this->validated()];
    }
}
