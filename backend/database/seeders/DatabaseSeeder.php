<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Visitation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Model events stay on: Patient assigns patient_number in its creating hook.

    /**
     * Seed the application's database with fake dev data. Never real data.
     */
    public function run(): void
    {
        $this->call([AccessControlSeeder::class, CheckpointSeeder::class]);

        // Dev logins, all with password "password": one user per role
        $users = Role::all()->mapWithKeys(function (Role $role) {
            $user = AppUser::factory()->create(['username' => $role->code === 'admin' ? 'admin' : $role->code]);
            $user->roles()->attach($role);

            return [$role->code => $user];
        });

        // Staff without a login yet, to try creating users
        Staff::factory(5)->create();

        $patients = Patient::factory(20)->create(['created_by' => $users['receptionist']->id]);

        // A few patients waiting today, so the queue has something to show
        foreach (['reception', 'triage', 'triage', 'consultation', 'lab', 'pharmacy'] as $i => $checkpoint) {
            Visitation::factory()->at($checkpoint)->create([
                'patient_id' => $patients[$i]->id,
                'registered_by' => $users['receptionist']->id,
                'priority' => $i === 2 ? 'urgent' : 'routine',
                'arrived_at' => now()->subMinutes(90 - $i * 10),
            ]);
        }
    }
}
