<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_every_permission(): void
    {
        $this->seed(AccessControlSeeder::class);

        $admin = AppUser::factory()->create();
        $admin->roles()->attach(Role::firstWhere('code', 'admin'));

        $this->assertEqualsCanonicalizing(array_keys(AccessControlSeeder::PERMISSIONS), $admin->permissionCodes());
    }

    public function test_nurse_cannot_register_patients(): void
    {
        $this->seed(AccessControlSeeder::class);

        $nurse = AppUser::factory()->create();
        $nurse->roles()->attach(Role::firstWhere('code', 'nurse_midwife'));

        $this->assertTrue($nurse->hasPermission('patient.view'));
        $this->assertFalse($nurse->hasPermission('patient.create'));
    }

    public function test_running_twice_does_not_duplicate_and_resyncs_roles(): void
    {
        $this->seed(AccessControlSeeder::class);
        Role::firstWhere('code', 'nurse_midwife')->permissions()->attach(Permission::firstWhere('code', 'patient.create'));

        $this->seed(AccessControlSeeder::class);

        $this->assertSame(count(AccessControlSeeder::PERMISSIONS), Permission::count());
        $this->assertSame(count(AccessControlSeeder::ROLES), Role::count());
        $this->assertFalse(Role::firstWhere('code', 'nurse_midwife')->permissions()->where('code', 'patient.create')->exists());
    }
}
