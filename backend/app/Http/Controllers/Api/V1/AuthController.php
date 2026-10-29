<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Resources\V1\AuthUserResource;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Log in with username and password and receive a bearer token.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = AppUser::firstWhere('username', $request->validated('username'));

        if (! $user || ! $user->is_active || ! Hash::check($request->validated('password'), $user->password_hash)) {
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $user->createToken($request->validated('device_name', 'web'))->plainTextToken,
            'user' => AuthUserResource::make($user->load('staff')),
        ]);
    }

    /**
     * The logged-in user, with their permission codes.
     */
    public function me(Request $request): AuthUserResource
    {
        return AuthUserResource::make($request->user()->load('staff'));
    }

    /**
     * Revoke the token used for this request.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
