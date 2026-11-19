<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('refunds.manage');
    }

    public function view(User $user, Refund $refund): bool
    {
        if ($refund->user_id === $user->id) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('refunds.manage');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('refunds.manage');
    }

    public function update(User $user, Refund $refund): bool
    {
        return $user->isAdministrator() && $user->hasPermission('refunds.manage');
    }

    public function delete(User $user, Refund $refund): bool
    {
        return false;
    }
}
