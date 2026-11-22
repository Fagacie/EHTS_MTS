<?php

namespace App\Policies;

use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Enums\ModuleStatus;
use App\Models\ModuleVideo;
use App\Models\User;

class ModuleVideoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function view(User $user, ModuleVideo $video): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function update(User $user, ModuleVideo $video): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function delete(User $user, ModuleVideo $video): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function watch(User $user, ModuleVideo $video): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isAdministrator() && $user->hasPermission('modules.manage')) {
            return true;
        }

        if (
            $video->status !== MaterialStatus::Published
            || $video->encoding_status !== EncodingStatus::Ready
            || $video->module === null
            || $video->module->status !== ModuleStatus::Published
        ) {
            return false;
        }

        return $user->hasActiveModuleEntitlement($video->module_id);
    }
}
