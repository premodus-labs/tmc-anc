<?php

namespace App\Http\Requests\V1;

use App\Models\VisitationVitals;
use Illuminate\Foundation\Http\FormRequest;

class StoreVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordVitals', $this->route('visitation'));
    }

    /**
     * Ranges are wide on purpose: they catch typing slips (a temperature of 365),
     * not abnormal readings, which clinicians need to be able to record.
     */
    public function rules(): array
    {
        $atLeastOne = 'required_without_all:'.implode(',', VisitationVitals::MEASUREMENTS);

        return [
            'temperature_c' => ['nullable', $atLeastOne, 'numeric', 'between:25,45'],
            'bp_systolic' => ['nullable', $atLeastOne, 'required_with:bp_diastolic', 'integer', 'between:40,300', 'gt:bp_diastolic'],
            'bp_diastolic' => ['nullable', $atLeastOne, 'required_with:bp_systolic', 'integer', 'between:20,200'],
            'pulse_bpm' => ['nullable', $atLeastOne, 'integer', 'between:20,250'],
            'resp_rate' => ['nullable', $atLeastOne, 'integer', 'between:4,80'],
            'spo2_pct' => ['nullable', $atLeastOne, 'integer', 'between:50,100'],
            'weight_kg' => ['nullable', $atLeastOne, 'numeric', 'between:0.5,350'],
            'height_cm' => ['nullable', $atLeastOne, 'numeric', 'between:30,250'],
            'muac_cm' => ['nullable', $atLeastOne, 'numeric', 'between:5,60'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required_without_all' => 'Record at least one measurement.',
            'bp_systolic.gt' => 'Systolic pressure must be higher than diastolic.',
        ];
    }
}
