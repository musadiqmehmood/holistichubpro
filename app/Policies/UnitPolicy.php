<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Unit;

class UnitPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(config('rbac.super_admin_role')) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('units.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('units.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('units.edit');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('units.delete');
    }
}
