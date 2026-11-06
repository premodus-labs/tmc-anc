<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\StaffResource;
use App\Models\AppUser;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Read-only staff list for picking who gets a login. Staff records
 * themselves belong to the HR slice.
 */
class StaffController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AppUser::class);

        $request->validate([
            'search' => ['sometimes', 'string', 'max:100'],
            'has_login' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $staff = Staff::query()
            ->withExists('appUser')
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where(
                fn ($q) => $q->whereLike('first_name', "%{$search}%")
                    ->orWhereLike('last_name', "%{$search}%")
                    ->orWhereLike('employee_number', "%{$search}%")
            ))
            ->when($request->has('has_login'), fn ($query) => $request->boolean('has_login')
                ? $query->has('appUser')
                : $query->doesntHave('appUser'))
            ->orderBy('last_name')
            ->paginate($request->integer('per_page', 25));

        return StaffResource::collection($staff);
    }
}
