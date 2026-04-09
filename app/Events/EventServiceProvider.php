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

// Your Custom Events
use App\Events\RoleChanged;
use App\Events\PermissionChanged;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // These run SYNCHRONOUSLY (not queued) so IP is available
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

        // Custom events
        RoleChanged::class => [
            LogRoleChange::class,
        ],
        PermissionChanged::class => [
            LogPermissionChange::class,
        ],
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
