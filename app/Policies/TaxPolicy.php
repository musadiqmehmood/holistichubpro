<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tax;

class TaxPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRoleInBranch(config('rbac.super_admin_role')) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInBranch('taxes.view');
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->hasPermissionInBranch('taxes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInBranch('taxes.create');
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->hasPermissionInBranch('taxes.edit');
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->hasPermissionInBranch('taxes.delete');
    }
}
