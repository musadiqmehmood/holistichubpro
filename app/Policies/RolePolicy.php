<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(config('rbac.super_admin_role'))) {
            if ($ability === 'delete') {
                return null;
            }
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === config('rbac.super_admin_role')) {
            return false;
        }
        return $user->hasPermissionTo('roles.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->name === config('rbac.super_admin_role')) {
            return false;
        }

        if ($role->users()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('roles.delete');
    }
}
