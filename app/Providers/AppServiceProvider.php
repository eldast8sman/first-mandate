<?php

namespace App\Providers;

use App\Models\Property;
use App\Models\PropertyUnit;
use App\Observers\PropertyObserver;
use App\Observers\PropertyUnitObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Property::observe(PropertyObserver::class);
        PropertyUnit::observe(PropertyUnitObserver::class);
    }
}
