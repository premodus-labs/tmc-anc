<?php

namespace App\Policies;

use App\Models\AppUser;

class AppUserPolicy
{
    public function viewAny(AppUser $user): bool
    {
        return $user->hasPermission('user.view');
    }

    public function view(AppUser $user, AppUser $target): bool
    {
        return $user->hasPermission('user.view');
    }

    public function create(AppUser $user): bool
    {
        return $user->hasPermission('user.manage');
    }

    public function update(AppUser $user, AppUser $target): bool
    {
        return $user->hasPermission('user.manage');
    }

    /**
     * Roles, activation and password resets. Not on your own account, so an
     * admin cannot lock themselves out or grant themselves more access.
     */
    public function manageAccess(AppUser $user, AppUser $target): bool
    {
        return $user->hasPermission('user.manage') && ! $user->is($target);
    }
}
