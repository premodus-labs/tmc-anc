<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Model events stay on: Patient assigns patient_number in its creating hook.

    /**
     * Seed the application's database with fake dev data. Never real data.
     */
    public function run(): void
    {
        $permissions = collect([
            'patient.view' => 'View patient records',
            'patient.create' => 'Register patients',
            'patient.update' => 'Edit patient details',
        ])->map(fn ($description, $code) => Permission::firstOrCreate(
            ['code' => $code],
            ['description' => $description],
        ));

        $admin = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        $admin->permissions()->syncWithoutDetaching($permissions->pluck('id'));

        // Dev login: admin / password
        $user = AppUser::factory()->create(['username' => 'admin']);
        $user->roles()->attach($admin);

        Patient::factory(20)->create(['created_by' => $user->id]);
    }
}
