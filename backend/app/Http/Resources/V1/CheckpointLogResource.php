<?php

namespace App\Http\Resources\V1;

use App\Models\VisitationCheckpointLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VisitationCheckpointLog
 */
class CheckpointLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checkpoint' => CheckpointResource::make($this->whenLoaded('checkpoint')),
            'entered_at' => $this->entered_at?->toIso8601String(),
            'cleared_at' => $this->cleared_at?->toIso8601String(),
            'cleared_by' => $this->cleared_by,
            'skipped' => $this->skipped,
            'note' => $this->note,
        ];
    }
}
