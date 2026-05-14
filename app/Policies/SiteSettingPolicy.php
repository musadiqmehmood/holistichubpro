<?php

namespace App\Policies;

use App\Models\SiteSetting;
use App\Models\User;

class SiteSettingPolicy
{
    // super-admin is caught by Gate::before in AuthServiceProvider
    public function manage(User $user): bool
    {
        return $user->hasPermissionInBranch('settings.manage');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionInBranch('settings.view');
    }
}
