<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaxGroup;

class TaxGroupPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRoleInBranch(config('rbac.super_admin_role')) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInBranch('tax_groups.view');
    }

    public function view(User $user, TaxGroup $taxGroup): bool
    {
        return $user->hasPermissionInBranch('tax_groups.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInBranch('tax_groups.create');
    }

    public function update(User $user, TaxGroup $taxGroup): bool
    {
        return $user->hasPermissionInBranch('tax_groups.edit');
    }

    public function delete(User $user, TaxGroup $taxGroup): bool
    {
        return $user->hasPermissionInBranch('tax_groups.delete');
    }
}
