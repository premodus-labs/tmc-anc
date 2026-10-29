<?php

namespace App\Http\Resources\V1;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppUser
 */
class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'staff' => $this->whenLoaded('staff', fn () => [
                'id' => $this->staff->id,
                'first_name' => $this->staff->first_name,
                'last_name' => $this->staff->last_name,
            ]),
            'permissions' => $this->permissionCodes(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
