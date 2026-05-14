<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PaymentType;

class PaymentTypePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRoleInBranch(config('rbac.super_admin_role')) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInBranch('payment_types.view');
    }

    public function view(User $user, PaymentType $paymentType): bool
    {
        return $user->hasPermissionInBranch('payment_types.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInBranch('payment_types.create');
    }

    public function update(User $user, PaymentType $paymentType): bool
    {
        return $user->hasPermissionInBranch('payment_types.edit');
    }

    public function delete(User $user, PaymentType $paymentType): bool
    {
        return $user->hasPermissionInBranch('payment_types.delete');
    }
}
