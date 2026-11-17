<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $username, string $password = 'password', string $device = 'web'): string
    {
        return $this->postJson('/api/v1/auth/login', compact('username', 'password') + ['device_name' => $device])
            ->assertOk()
            ->json('token');
    }

    public function test_user_changes_own_password_and_other_devices_are_logged_out(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);
        $phone = $this->login('nurse1', device: 'phone');
        $desk = $this->login('nurse1', device: 'desk');

        $this->withToken($desk)->putJson('/api/v1/auth/password', [
            'current_password' => 'password',
            'password' => 'newSecret123',
            'password_confirmation' => 'newSecret123',
        ])->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($desk)->getJson('/api/v1/auth/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($phone)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'newSecret123'])->assertOk();
    }

    public function test_change_requires_the_current_password(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);
        $token = $this->login('nurse1');

        $this->withToken($token)->putJson('/api/v1/auth/password', [
            'current_password' => 'wrong',
            'password' => 'newSecret123',
            'password_confirmation' => 'newSecret123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_new_password_must_be_strong_and_confirmed(): void
    {
        AppUser::factory()->create(['username' => 'nurse1']);
        $token = $this->login('nurse1');

        $this->withToken($token)->putJson('/api/v1/auth/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = AppUser::factory()->create(['username' => 'nurse1']);
        $this->login('nurse1', device: 'phone');
        $desk = $this->login('nurse1', device: 'desk');

        $this->withToken($desk)->postJson('/api/v1/auth/logout-all')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }
}
