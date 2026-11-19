<?php

namespace App\Policies;

use App\Models\AppUser;
use App\Models\Visitation;

class VisitationPolicy
{
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermission('visit.view');
    }

    public function view(AppUser $user, Visitation $visit): bool
    {
        return $user->hasPermission('visit.view');
    }

    /**
     * Check a patient in.
     */
    public function create(AppUser $user): bool
    {
        return $user->hasPermission('visit.create');
    }

    /**
     * Move the visit between checkpoints or close it.
     */
    public function update(AppUser $user, Visitation $visit): bool
    {
        return $user->hasPermission('visit.update');
    }

    public function recordVitals(AppUser $user, Visitation $visit): bool
    {
        return $user->hasPermission('vitals.record');
    }
}
