<?php

namespace App\Policies;

use App\Enums\MaterialStatus;
use App\Enums\ModuleStatus;
use App\Models\ModuleMaterial;
use App\Models\User;

class ModuleMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function view(User $user, ModuleMaterial $material): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function update(User $user, ModuleMaterial $material): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function delete(User $user, ModuleMaterial $material): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function download(User $user, ModuleMaterial $material): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isAdministrator() && $user->hasPermission('modules.manage')) {
            return true;
        }

        if (
            $user->isInstructor()
            && $material->module !== null
            && $material->module->trainingSessions()->whereHas('instructors', fn ($q) => $q->where('users.id', $user->id))->exists()
        ) {
            return true;
        }

        if (
            $material->status !== MaterialStatus::Published
            || ! $material->is_downloadable
            || $material->module === null
            || $material->module->status !== ModuleStatus::Published
        ) {
            return false;
        }

        return $user->hasActiveModuleEntitlement($material->module_id);
    }
}
