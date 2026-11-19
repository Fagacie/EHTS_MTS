<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function view(User $user, Module $module): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function update(User $user, Module $module): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function delete(User $user, Module $module): bool
    {
        if ($module->materials()->withTrashed()->exists()) {
            return false;
        }

        if ($module->trainingSessions()->exists()) {
            return false;
        }

        if ($module->videos()->withTrashed()->exists() || $module->entitlements()->exists() || $module->certificates()->exists()) {
            return false;
        }

        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }
}
