<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

//        Gate::before(function ($user, $ability) {
//            return $user->hasRole('super-admin') ? true : null;
//        });

        // NO OBSERVERS - using explicit controller logging instead
        // (Spatie models don't fire events properly, and observers cause DB errors)
    }
}
