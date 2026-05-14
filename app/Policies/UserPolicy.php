<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // ✅ Updated: super-admin with branch scoping
    public function before(User $user): ?bool
    {
        if ($user->hasRoleInBranch(config('rbac.super_admin_role'))) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInBranch('users.view');
    }

    public function view(User $user, User $model): bool
    {
        // Can view own profile or have permission
        return $user->id === $model->id || $user->hasPermissionInBranch('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInBranch('users.create');
    }

    public function update(User $user, User $model): bool
    {
        // Cannot update super-admin unless you're super-admin (handled by before)
        if ($model->hasRole(config('rbac.super_admin_role'))) {
            return false;
        }
        return $user->hasPermissionInBranch('users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete super-admin
        if ($model->hasRole(config('rbac.super_admin_role'))) {
            return false;
        }

        return $user->hasPermissionInBranch('users.delete');
    }
}
