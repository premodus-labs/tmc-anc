<?php

namespace App\Http\Resources\V1;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Staff
 */
class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'employment_status' => $this->employment_status,
            'has_login' => (bool) $this->app_user_exists,
        ];
    }
}
