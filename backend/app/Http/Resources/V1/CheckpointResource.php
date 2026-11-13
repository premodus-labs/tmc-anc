<?php

namespace App\Http\Resources\V1;

use App\Models\Checkpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Checkpoint
 */
class CheckpointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'sequence' => $this->sequence,
        ];
    }
}
