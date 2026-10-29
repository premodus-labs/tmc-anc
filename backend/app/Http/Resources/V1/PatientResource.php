<?php

namespace App\Http\Resources\V1;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_number' => $this->patient_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'sex' => $this->sex,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'dob_estimated' => $this->dob_estimated,
            'national_id' => $this->national_id,
            'address' => $this->address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
