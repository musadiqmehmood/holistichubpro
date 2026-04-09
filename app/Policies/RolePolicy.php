<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * CRITICAL FIX: Super-admin bypass BUT with protection checks
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            // Protect super-admin role from deletion even by super-admins
            if ($ability === 'delete') {
                return null; // Let delete() method handle protection
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
        // Protect super-admin role
        if ($role->name === 'super-admin') {
            return false;
        }
        return $user->hasPermissionTo('roles.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        // CRITICAL: Protect super-admin role from deletion
        if ($role->name === 'super-admin') {
            return false;
        }

        // Check if role has users
        if ($role->users()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('roles.delete');
    }
}
