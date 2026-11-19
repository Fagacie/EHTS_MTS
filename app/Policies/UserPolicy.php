<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdministrator() && $actor->hasPermission('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->id === $target->id
            || ($actor->isAdministrator() && $actor->hasPermission('users.view'));
    }

    public function create(User $actor): bool
    {
        return $actor->isAdministrator() && $actor->hasPermission('users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->isAdministrator() && $actor->hasPermission('users.manage');
    }

    public function delete(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        return $actor->isAdministrator() && $actor->hasPermission('users.manage');
    }
}
