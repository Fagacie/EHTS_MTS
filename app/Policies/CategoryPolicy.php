<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        if ($category->modules()->withTrashed()->exists()) {
            return false;
        }

        return $user->isAdministrator() && $user->hasPermission('modules.manage');
    }
}
