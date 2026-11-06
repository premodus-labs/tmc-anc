<?php

namespace App\Http\Resources\V1;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A login as seen by administrators.
 *
 * @mixin AppUser
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'is_active' => $this->is_active,
            'staff' => $this->whenLoaded('staff', fn () => [
                'id' => $this->staff->id,
                'employee_number' => $this->staff->employee_number,
                'first_name' => $this->staff->first_name,
                'last_name' => $this->staff->last_name,
            ]),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
