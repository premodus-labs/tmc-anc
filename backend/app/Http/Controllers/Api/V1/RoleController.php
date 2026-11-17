<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RoleResource;
use App\Models\AppUser;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Every role with its permission codes, for the role picker.
     * Roles themselves are defined in AccessControlSeeder, not through the API.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AppUser::class);

        return RoleResource::collection(Role::with('permissions')->orderBy('name')->get());
    }
}
