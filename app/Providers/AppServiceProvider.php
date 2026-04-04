<?php

namespace App\Providers;

use App\Models\BusinessRequirement;
use App\Models\TechnicalRequirement;
use App\Observers\BusinessRequirementObserver;
use App\Observers\TechnicalRequirementObserver;
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
        BusinessRequirement::observe(BusinessRequirementObserver::class);
        TechnicalRequirement::observe(TechnicalRequirementObserver::class);
    }
}
