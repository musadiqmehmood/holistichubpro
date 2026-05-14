<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Unit;

class UnitPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRoleInBranch(config('rbac.super_admin_role')) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInBranch('units.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->hasPermissionInBranch('units.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInBranch('units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->hasPermissionInBranch('units.edit');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasPermissionInBranch('units.delete');
    }
}
