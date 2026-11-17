<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ResetUserPasswordRequest;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\SyncUserRolesRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\AppUser;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Staff logins and their roles. Users are deactivated, never deleted,
 * because clinical records point at them.
 */
class UserController extends Controller
{
    /**
     * List logins. Search matches username or staff name.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AppUser::class);

        $request->validate([
            'search' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $users = AppUser::query()
            ->with(['staff', 'roles'])
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where(
                fn ($q) => $q->whereLike('username', "%{$search}%")
                    ->orWhereHas('staff', fn ($s) => $s->whereLike('first_name', "%{$search}%")
                        ->orWhereLike('last_name', "%{$search}%"))
            ))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('username')
            ->paginate($request->integer('per_page', 25));

        return UserResource::collection($users);
    }

    /**
     * Create a login for a staff member.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = AppUser::create($request->safe()->only(['staff_id', 'username']) + [
                'password_hash' => $request->validated('password'),
            ]);
            $this->applyRoles($user, $request->validated('roles', []), $request->user());

            return $user;
        });

        return UserResource::make($user->refresh()->load(['staff', 'roles']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AppUser $user): UserResource
    {
        Gate::authorize('view', $user);

        return UserResource::make($user->load(['staff', 'roles']));
    }

    /**
     * Change username, or activate/deactivate. Deactivating logs the user out everywhere.
     */
    public function update(UpdateUserRequest $request, AppUser $user): UserResource
    {
        $user->update($request->validated());

        if ($user->wasChanged('is_active') && ! $user->is_active) {
            $user->tokens()->delete();
        }

        return UserResource::make($user->load(['staff', 'roles']));
    }

    /**
     * Replace the user's roles with exactly these role codes.
     */
    public function syncRoles(SyncUserRolesRequest $request, AppUser $user): UserResource
    {
        DB::transaction(fn () => $this->applyRoles($user, $request->validated('roles'), $request->user()));

        return UserResource::make($user->load(['staff', 'roles']));
    }

    /**
     * Set a new password for someone who forgot theirs. Logs them out everywhere.
     */
    public function resetPassword(ResetUserPasswordRequest $request, AppUser $user): Response
    {
        $user->update(['password_hash' => $request->validated('password')]);
        $user->tokens()->delete();

        return response()->noContent();
    }

    /**
     * Roles kept stay untouched, so their assigned_at and assigned_by stay true.
     *
     * @param  list<string>  $codes
     */
    private function applyRoles(AppUser $user, array $codes, AppUser $actor): void
    {
        $wanted = Role::whereIn('code', $codes)->pluck('id');
        $current = $user->roles()->pluck('role.id');

        $user->roles()->detach($current->diff($wanted));
        $user->roles()->attach($wanted->diff($current), ['assigned_by' => $actor->id]);
    }
}
