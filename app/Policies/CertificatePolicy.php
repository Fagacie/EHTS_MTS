<?php

namespace App\Policies;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && (
            $user->hasPermission('certificates.approve')
            || $user->hasPermission('certificates.revoke')
        );
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if ($user->id === $certificate->user_id && $user->isActive()) {
            return true;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('certificates.approve');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->isAdministrator() && (
            $user->hasPermission('certificates.approve')
            || $user->hasPermission('certificates.revoke')
        );
    }

    public function revoke(User $user, Certificate $certificate): bool
    {
        return $user->isAdministrator() && $user->hasPermission('certificates.revoke');
    }

    /**
     * Download gate: administrators may inspect stored PDFs; trainees may download ONLY non-revoked issued certificates.
     */
    public function download(User $user, Certificate $certificate): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($this->viewAny($user)) {
            return true;
        }

        if ($user->id !== $certificate->user_id) {
            return false;
        }

        return $certificate->status === CertificateStatus::Issued;
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return false;
    }
}
