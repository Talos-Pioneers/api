<?php

namespace App\Providers;

use App\Models\Blueprint;
use App\Policies\BlueprintPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
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
        Gate::policy(Blueprint::class, BlueprintPolicy::class);

        Http::macro('warfarin', function () {
            return Http::baseUrl('https://api.warfarin.wiki/v1');
        });
    }
}
