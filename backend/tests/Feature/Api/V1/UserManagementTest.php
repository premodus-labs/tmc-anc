<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use App\Models\Role;
use App\Models\Staff;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    private function actingAsAdmin(): AppUser
    {
        $admin = AppUser::factory()->create(['username' => 'admin']);
        $admin->roles()->attach(Role::firstWhere('code', 'admin'));
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_creates_a_login_for_a_staff_member(): void
    {
        $admin = $this->actingAsAdmin();
        $staff = Staff::factory()->create();

        $this->postJson('/api/v1/users', [
            'staff_id' => $staff->id,
            'username' => 'mbanda',
            'password' => 'Welcome123',
            'roles' => ['receptionist'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.username', 'mbanda')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.staff.id', $staff->id)
            ->assertJsonPath('data.roles.0.code', 'receptionist')
            ->assertJsonMissingPath('data.password_hash');

        $this->assertDatabaseHas('user_role', ['assigned_by' => $admin->id]);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['username' => 'mbanda', 'password' => 'Welcome123'])
            ->assertOk()
            ->assertJsonPath('user.permissions', ['patient.create', 'patient.update', 'patient.view']);
    }

    public function test_create_validates_input(): void
    {
        $this->actingAsAdmin();
        $taken = AppUser::factory()->create(['username' => 'taken']);

        $this->postJson('/api/v1/users', [
            'staff_id' => $taken->staff_id,
            'username' => 'taken',
            'password' => 'weak',
            'roles' => ['no_such_role'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['staff_id', 'username', 'password', 'roles.0'])
            ->assertJsonPath('errors.staff_id.0', 'This staff member already has a login.');
    }

    public function test_usernames_are_stored_lowercase_and_unique_regardless_of_case(): void
    {
        $this->actingAsAdmin();
        AppUser::factory()->create(['username' => 'mbanda']);

        $this->postJson('/api/v1/users', [
            'staff_id' => Staff::factory()->create()->id,
            'username' => 'MBanda',
            'password' => 'Welcome123',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');

        $this->postJson('/api/v1/users', [
            'staff_id' => Staff::factory()->create()->id,
            'username' => 'JPhiri',
            'password' => 'Welcome123',
        ])->assertCreated()->assertJsonPath('data.username', 'jphiri');
    }

    public function test_new_user_without_roles_can_log_in_but_has_no_permissions(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', [
            'staff_id' => Staff::factory()->create()->id,
            'username' => 'newstaff',
            'password' => 'Welcome123',
        ])->assertCreated()->assertJsonCount(0, 'data.roles');

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['username' => 'newstaff', 'password' => 'Welcome123'])
            ->assertOk()
            ->assertJsonPath('user.permissions', []);
    }

    public function test_create_rejects_unknown_staff(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', ['staff_id' => 9999, 'username' => 'ghost', 'password' => 'Welcome123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('staff_id');
    }

    public function test_shows_a_user_and_404s_for_a_missing_one(): void
    {
        $this->actingAsAdmin();
        $user = AppUser::factory()->create(['username' => 'mbanda']);

        $this->getJson("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.username', 'mbanda')
            ->assertJsonMissingPath('data.password_hash');

        $this->getJson('/api/v1/users/9999')->assertNotFound();
    }

    public function test_reactivated_user_can_log_in_again(): void
    {
        $this->actingAsAdmin();
        $user = AppUser::factory()->inactive()->create(['username' => 'nurse1']);

        $this->patchJson("/api/v1/users/{$user->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'password'])->assertOk();
    }

    public function test_reset_password_must_be_confirmed_and_strong(): void
    {
        $this->actingAsAdmin();
        $user = AppUser::factory()->create();

        $this->putJson("/api/v1/users/{$user->id}/password", ['password' => 'short', 'password_confirmation' => 'other'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_user_view_alone_is_read_only(): void
    {
        $viewer = AppUser::factory()->withPermissions('user.view')->create();
        Sanctum::actingAs($viewer);
        $other = AppUser::factory()->create();

        $this->getJson('/api/v1/users')->assertOk();
        $this->getJson("/api/v1/users/{$other->id}")->assertOk();
        $this->getJson('/api/v1/roles')->assertOk();
        $this->getJson('/api/v1/staff')->assertOk();

        $this->postJson('/api/v1/users', [
            'staff_id' => Staff::factory()->create()->id,
            'username' => 'sneaky',
            'password' => 'Welcome123',
        ])->assertForbidden();
        $this->patchJson("/api/v1/users/{$other->id}", ['username' => 'renamed'])->assertForbidden();
        $this->putJson("/api/v1/users/{$other->id}/password", [
            'password' => 'Reset12345',
            'password_confirmation' => 'Reset12345',
        ])->assertForbidden();
    }

    public function test_lists_and_searches_users(): void
    {
        $this->actingAsAdmin();
        AppUser::factory()->create(['username' => 'mbanda']);
        AppUser::factory()->inactive()->create(['username' => 'jphiri']);

        $this->getJson('/api/v1/users?search=band')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'mbanda');

        $this->getJson('/api/v1/users?is_active=0')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'jphiri');
    }

    public function test_deactivating_a_user_logs_them_out(): void
    {
        $this->actingAsAdmin();
        $nurse = AppUser::factory()->create();
        $nurse->createToken('web');

        $this->patchJson("/api/v1/users/{$nurse->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertSame(0, $nurse->tokens()->count());
    }

    public function test_sync_roles_replaces_roles_and_keeps_unchanged_assignments(): void
    {
        $admin = $this->actingAsAdmin();
        $user = AppUser::factory()->create();
        $user->roles()->attach(Role::firstWhere('code', 'clinician'), ['assigned_by' => null]);

        $this->putJson("/api/v1/users/{$user->id}/roles", ['roles' => ['clinician', 'receptionist']])
            ->assertOk()
            ->assertJsonCount(2, 'data.roles');

        $this->assertDatabaseHas('user_role', ['user_id' => $user->id, 'role_id' => Role::firstWhere('code', 'clinician')->id, 'assigned_by' => null]);
        $this->assertDatabaseHas('user_role', ['user_id' => $user->id, 'role_id' => Role::firstWhere('code', 'receptionist')->id, 'assigned_by' => $admin->id]);

        $this->putJson("/api/v1/users/{$user->id}/roles", ['roles' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.roles');
    }

    public function test_admin_resets_a_password_and_old_tokens_stop_working(): void
    {
        $this->actingAsAdmin();
        $nurse = AppUser::factory()->create(['username' => 'nurse1']);
        $nurse->createToken('web');

        $this->putJson("/api/v1/users/{$nurse->id}/password", [
            'password' => 'Reset12345',
            'password_confirmation' => 'Reset12345',
        ])->assertNoContent();

        $this->assertSame(0, $nurse->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['username' => 'nurse1', 'password' => 'Reset12345'])->assertOk();
    }

    public function test_admin_cannot_change_own_access(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/v1/users/{$admin->id}", ['is_active' => false])->assertForbidden();
        $this->putJson("/api/v1/users/{$admin->id}/roles", ['roles' => []])->assertForbidden();
        $this->putJson("/api/v1/users/{$admin->id}/password", [
            'password' => 'Reset12345',
            'password_confirmation' => 'Reset12345',
        ])->assertForbidden();

        // A username change is not an access change
        $this->patchJson("/api/v1/users/{$admin->id}", ['username' => 'admin2'])->assertOk();
    }

    public function test_non_admins_cannot_manage_users(): void
    {
        $nurse = AppUser::factory()->create();
        $nurse->roles()->attach(Role::firstWhere('code', 'nurse_midwife'));
        Sanctum::actingAs($nurse);
        $other = AppUser::factory()->create();

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->getJson('/api/v1/roles')->assertForbidden();
        $this->getJson('/api/v1/staff')->assertForbidden();
        $this->postJson('/api/v1/users', [])->assertForbidden();
        $this->patchJson("/api/v1/users/{$other->id}", ['is_active' => false])->assertForbidden();
        $this->putJson("/api/v1/users/{$other->id}/roles", ['roles' => ['admin']])->assertForbidden();
    }

    public function test_users_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();
        $user = AppUser::factory()->create();

        $this->deleteJson("/api/v1/users/{$user->id}")->assertMethodNotAllowed();
    }

    public function test_lists_roles_with_permissions(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonCount(count(AccessControlSeeder::ROLES), 'data')
            ->assertJsonStructure(['data' => [['code', 'name', 'permissions']]]);
    }

    public function test_lists_staff_without_a_login(): void
    {
        $admin = $this->actingAsAdmin();
        $free = Staff::factory()->create();

        $this->getJson('/api/v1/staff?has_login=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $free->id)
            ->assertJsonPath('data.0.has_login', false);

        $this->getJson('/api/v1/staff?has_login=1')
            ->assertJsonPath('data.0.id', $admin->staff_id)
            ->assertJsonPath('data.0.has_login', true);
    }
}
