<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Patient;

class PatientPolicy
{
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermission('patient.view');
    }

    public function view(AppUser $user, Patient $patient): bool
    {
        return $user->hasPermission('patient.view');
    }

    public function create(AppUser $user): bool
    {
        return $user->hasPermission('patient.create');
    }

    public function update(AppUser $user, Patient $patient): bool
    {
        return $user->hasPermission('patient.update');
    }
}
