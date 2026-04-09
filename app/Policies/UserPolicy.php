<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // ✅ Updated: super-admin instead of admin
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function view(User $user, User $model): bool
    {
        // Can view own profile or have permission
        return $user->id === $model->id || $user->hasPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $model): bool
    {
        // Cannot update super-admin unless you're super-admin (handled by before)
        if ($model->hasRole('super-admin')) {
            return false;
        }
        return $user->hasPermissionTo('users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete super-admin
        if ($model->hasRole('super-admin')) {
            return false;
        }

        return $user->hasPermissionTo('users.delete');
    }
}
