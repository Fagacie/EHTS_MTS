<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Enums\SessionMaterialStatus;
use App\Models\SessionMaterial;
use App\Models\User;

class SessionMaterialPolicy
{
    public function download(User $user, SessionMaterial $material): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isAdministrator() && $user->hasPermission('sessions.manage')) {
            return true;
        }

        if ($user->isInstructor() && $material->trainingSession?->instructors()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($material->status !== SessionMaterialStatus::Published || ! $material->is_downloadable) {
            return false;
        }

        if ($material->entitlements()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists()) {
            return true;
        }

        return $material->trainingSession?->bookings()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->exists() ?? false;
    }
}
