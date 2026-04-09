<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Spatie Permission Events
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;

// Your Listeners
use App\Listeners\LogRoleChange;
use App\Listeners\LogPermissionChange;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Only Spatie events - remove custom events to prevent duplicates
        RoleAttached::class => [
            LogRoleChange::class,
        ],
        RoleDetached::class => [
            LogRoleChange::class,
        ],
        PermissionAttached::class => [
            LogPermissionChange::class,
        ],
        PermissionDetached::class => [
            LogPermissionChange::class,
        ],
        // REMOVED: Custom RoleChanged and PermissionChanged to prevent duplicates
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
