<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tax;

class TaxPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('taxes.view');
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->hasPermissionTo('taxes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('taxes.create');
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->hasPermissionTo('taxes.edit');
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->hasPermissionTo('taxes.delete');
    }
}
