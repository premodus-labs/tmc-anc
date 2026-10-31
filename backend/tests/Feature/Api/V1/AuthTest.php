<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_receives_a_token(): void
    {
        AppUser::factory()->withPermissions('patient.view')->create(['username' => 'nurse1']);

        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'username', 'staff', 'permissions']])
            ->assertJsonPath('user.permissions', ['patient.view']);

        $this->assertNotNull(AppUser::firstWhere('username', 'nurse1')->last_login_at);
    }

    public function test_wrong_password_is_rejected(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);

        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        AppUser::factory()->inactive()->create(['username' => 'nurse1']);

        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_token_gives_access_to_me_and_logout_revokes_it(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);

        $token = $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'nurse1');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guest_cannot_reach_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_the_acting_user(): void
    {
        Sanctum::actingAs($user = AppUser::factory()->create());

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.id', $user->id);
    }

    public function test_deactivated_user_is_locked_out_with_an_existing_token(): void
    {
        $user = AppUser::factory()->create(['username' => 'nurse1']);

        $token = $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])
            ->json('token');

        $user->update(['is_active' => false]);

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_token_expires_after_the_configured_lifetime(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);

        $token = $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])
            ->json('token');

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
