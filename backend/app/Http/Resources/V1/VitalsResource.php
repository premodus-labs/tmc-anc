<?php

namespace App\Http\Resources\V1;

use App\Models\VisitationVitals;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VisitationVitals
 */
class VitalsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visitation_id' => $this->visitation_id,
            'temperature_c' => $this->temperature_c,
            'bp_systolic' => $this->bp_systolic,
            'bp_diastolic' => $this->bp_diastolic,
            'pulse_bpm' => $this->pulse_bpm,
            'resp_rate' => $this->resp_rate,
            'spo2_pct' => $this->spo2_pct,
            'weight_kg' => $this->weight_kg,
            'height_cm' => $this->height_cm,
            'muac_cm' => $this->muac_cm,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
