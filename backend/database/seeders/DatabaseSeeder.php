<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Patient;
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
        $this->call(AccessControlSeeder::class);

        // Dev logins, all with password "password": one user per role
        $users = Role::all()->mapWithKeys(function (Role $role) {
            $user = AppUser::factory()->create(['username' => $role->code === 'admin' ? 'admin' : $role->code]);
            $user->roles()->attach($role);

            return [$role->code => $user];
        });

        Patient::factory(20)->create(['created_by' => $users['receptionist']->id]);
    }
}
