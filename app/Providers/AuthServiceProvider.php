<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\SiteSetting;
use App\Models\SmtpSetting;
use App\Models\StoreSetting;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\PaymentTypePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\SmtpSettingPolicy;
use App\Policies\StoreSettingPolicy;
use App\Policies\TaxGroupPolicy;
use App\Policies\TaxPolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
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
        SiteSetting::class  => SiteSettingPolicy::class,
        StoreSetting::class => StoreSettingPolicy::class,
        SmtpSetting::class  => SmtpSettingPolicy::class,
        Tax::class         => TaxPolicy::class,
        TaxGroup::class    => TaxGroupPolicy::class,
        Unit::class        => UnitPolicy::class,
        PaymentType::class => PaymentTypePolicy::class,
        Currency::class    => CurrencyPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole(config('rbac.super_admin_role'))) {
                return true;
            }
            return null;
        });

        Gate::define('manage', function (User $user, string $model): bool {
            return $user->hasPermissionTo('settings.manage');
        });

        Validator::extend(
            'salon_password',
            function (string $attribute, mixed $value): bool {
                return (bool) preg_match(
                    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
                    $value
                );
            },
            'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        );

        Validator::extend(
            'not_recent_password',
            function (string $attribute, mixed $value): bool {
                $user = auth()->user();
                if (!$user) {
                    return true;
                }

                $recentHashes = \App\Models\PasswordHistory::where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->pluck('password');

                $hasMatch = false;
                foreach ($recentHashes as $hash) {
                    $hasMatch = $hasMatch || \Illuminate\Support\Facades\Hash::check($value, $hash);
                }

                return !$hasMatch;
            },
            'You cannot reuse any of your last 5 passwords.'
        );
    }
}
