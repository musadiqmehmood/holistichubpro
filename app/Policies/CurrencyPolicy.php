<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Currency;

class CurrencyPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRoleInBranch(config('rbac.super_admin_role')) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInBranch('currencies.view');
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->hasPermissionInBranch('currencies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInBranch('currencies.create');
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->hasPermissionInBranch('currencies.edit');
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $user->hasPermissionInBranch('currencies.delete');
    }
}
