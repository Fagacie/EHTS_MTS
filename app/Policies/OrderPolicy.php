<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('payments.manage');
    }

    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('payments.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdministrator() && $user->hasPermission('payments.manage');
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
