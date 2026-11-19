<?php

namespace App\Policies;

use App\Models\InstructorProfile;
use App\Models\User;

class InstructorProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('users.manage');
    }

    public function view(User $user, InstructorProfile $instructorProfile): bool
    {
        if ($user->id === $instructorProfile->user_id && $user->isInstructor()) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('users.manage');
    }

    public function update(User $user, InstructorProfile $instructorProfile): bool
    {
        if ($user->id === $instructorProfile->user_id && $user->isInstructor()) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('users.manage');
    }

    public function delete(User $user, InstructorProfile $instructorProfile): bool
    {
        return $user->isAdministrator() && $user->hasPermission('users.manage');
    }
}
