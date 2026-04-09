<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class       => UserPolicy::class,
        Role::class       => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        AuditLog::class   => AuditLogPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // ✅ CRITICAL: Super-admin bypass
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
            return null;
        });

        // ✅ FIXED: salon_password validator – accepts ANY non-alphanumeric character
        Validator::extend('salon_password', function ($attribute, $value) {
            $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
            return preg_match($pattern, $value);
        }, 'Password must contain at least one uppercase, one lowercase, one number and one special character.');

        Validator::extend('not_recent_password', function ($attribute, $value, $parameters, $validator) {
            $user = auth()->user();
            if (!$user) return true;

            $recentPasswords = \App\Models\PasswordHistory::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->pluck('password');

            foreach ($recentPasswords as $oldPassword) {
                if (\Hash::check($value, $oldPassword)) return false;
            }
            return true;
        }, 'You cannot use any of your last 5 passwords.');
    }
}
