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

    public function test_unknown_username_gets_the_same_error_as_a_wrong_password(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);

        $wrong = $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'wrong'])->json();
        $unknown = $this->postJson('/api/v1/auth/login', ['username' => 'nobody', 'password' => 'wrong'])->json();

        $this->assertSame($wrong, $unknown);
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_username_is_not_case_sensitive_at_login(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);

        $this->postJson('/api/v1/auth/login', ['username' => ' Nurse1 ', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.username', 'nurse1');
    }

    public function test_device_name_labels_the_token(): void
    {
        $user = AppUser::factory()->create(['username' => 'nurse1']);

        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password', 'device_name' => 'ward-tablet'])
            ->assertOk();

        $this->assertSame(['ward-tablet'], $user->tokens()->pluck('name')->all());
    }

    public function test_login_is_throttled_after_ten_attempts_a_minute(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);

        foreach (range(1, 10) as $attempt) {
            $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'wrong'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])->assertTooManyRequests();
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

    public function test_logout_only_revokes_the_token_it_was_sent_with(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);
        $login = fn (string $device) => $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password', 'device_name' => $device])->json('token');
        $phone = $login('phone');
        $desk = $login('desk');

        $this->withToken($desk)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($desk)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($phone)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_guest_cannot_reach_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout-all')->assertUnauthorized();
        $this->putJson('/api/v1/auth/password')->assertUnauthorized();
        $this->getJson('/api/v1/users')->assertUnauthorized();
        $this->postJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_guest_gets_a_json_401_even_without_an_accept_header(): void
    {
        $this->get('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_a_made_up_token_is_rejected(): void
    {
        $this->withToken('1|not-a-real-token')->getJson('/api/v1/auth/me')->assertUnauthorized();
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
