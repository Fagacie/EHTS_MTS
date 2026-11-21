<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

class TrainingSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('sessions.manage');
    }

    public function view(User $user, TrainingSession $trainingSession): bool
    {
        if ($user->isInstructor() && $trainingSession->instructors()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('sessions.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('sessions.manage');
    }

    public function update(User $user, TrainingSession $trainingSession): bool
    {
        return $user->isAdministrator() && $user->hasPermission('sessions.manage');
    }

    public function delete(User $user, TrainingSession $trainingSession): bool
    {
        if (
            $trainingSession->bookings()->exists()
            || $trainingSession->certificates()->exists()
            || $trainingSession->attendances()->exists()
            || $trainingSession->recordings()->exists()
            || $trainingSession->materials()->exists()
            || $trainingSession->sessionDocumentEntitlements()->exists()
        ) {
            return false;
        }

        return $user->isAdministrator() && $user->hasPermission('sessions.manage');
    }
}
