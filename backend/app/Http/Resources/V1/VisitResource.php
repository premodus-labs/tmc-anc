<?php

namespace App\Http\Resources\V1;

use App\Models\Visitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Visitation
 */
class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_number' => $this->visit_number,
            'patient_id' => $this->patient_id,
            'patient' => PatientResource::make($this->whenLoaded('patient')),
            'visit_type' => $this->visit_type,
            'priority' => $this->priority,
            'patient_consent' => $this->patient_consent,
            'status' => $this->status,
            'current_checkpoint' => CheckpointResource::make($this->whenLoaded('currentCheckpoint')),
            'arrived_at' => $this->arrived_at?->toIso8601String(),
            'registered_by' => $this->registered_by,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closed_by' => $this->closed_by,
            'closure_reason' => $this->closure_reason,
            'checkpoint_log' => CheckpointLogResource::collection($this->whenLoaded('checkpointLog')),
            'vitals' => VitalsResource::collection($this->whenLoaded('vitals')),
        ];
    }
}
