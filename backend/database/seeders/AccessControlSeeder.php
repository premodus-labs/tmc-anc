<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The permission catalogue and the clinic's standard roles. This is
 * reference data, not fake data: it is safe to run in every environment
 * and running it again brings roles back in line with this file.
 *
 * Add a permission here when you add a policy check for it.
 */
class AccessControlSeeder extends Seeder
{
    public const PERMISSIONS = [
        'patient.view' => 'View patient records',
        'patient.create' => 'Register patients',
        'patient.update' => 'Edit patient details',
    ];

    /**
     * Role code => [name, permission codes]. '*' grants every permission.
     */
    public const ROLES = [
        'admin' => ['Administrator', ['*']],
        'receptionist' => ['Receptionist', ['patient.view', 'patient.create', 'patient.update']],
        'nurse_midwife' => ['Nurse / Midwife', ['patient.view', 'patient.update']],
        'clinician' => ['Clinician', ['patient.view', 'patient.update']],
    ];

    public function run(): void
    {
        $permissions = collect(self::PERMISSIONS)->map(fn ($description, $code) => Permission::updateOrCreate(
            ['code' => $code],
            ['description' => $description],
        ));

        foreach (self::ROLES as $code => [$name, $codes]) {
            $granted = $codes === ['*'] ? $permissions : $permissions->only($codes);

            Role::updateOrCreate(['code' => $code], ['name' => $name])
                ->permissions()
                ->sync($granted->pluck('id'));
        }
    }
}
