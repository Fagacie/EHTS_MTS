<?php

namespace App\Policies;

use App\Models\ModuleEntitlement;
use App\Models\User;

class ModuleEntitlementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function view(User $user, ModuleEntitlement $entitlement): bool
    {
        if ($entitlement->user_id === $user->id) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function update(User $user, ModuleEntitlement $entitlement): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function delete(User $user, ModuleEntitlement $entitlement): bool
    {
        return false;
    }
}
