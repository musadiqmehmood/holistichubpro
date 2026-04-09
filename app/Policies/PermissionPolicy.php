<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            // Protect critical permissions
            if ($ability === 'delete') {
                return null; // Let delete() handle protection
            }
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('permissions.view');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('permissions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('permissions.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('permissions.edit');
    }

    public function delete(User $user, Permission $permission): bool
    {
        // Protect critical system permissions
        $protectedPermissions = ['roles.manage', 'permissions.manage', 'users.manage'];
        if (in_array($permission->name, $protectedPermissions)) {
            return false;
        }

        $hasRoles = $permission->roles()->exists();
        $hasDirectPermissions = \DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->exists();

        if ($hasRoles || $hasDirectPermissions) {
            return false;
        }

        return $user->hasPermissionTo('permissions.delete');
    }
}
