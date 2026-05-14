<?php

namespace App\Policies;

use App\Models\SmtpSetting;
use App\Models\User;

class SmtpSettingPolicy
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
