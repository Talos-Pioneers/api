<?php

namespace App\Providers;

use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Policies\BlueprintCollectionPolicy;
use App\Policies\BlueprintPolicy;
use App\Policies\TagPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Spatie\Tags\Tag;

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
        Gate::policy(BlueprintCollection::class, BlueprintCollectionPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);

        Http::macro('warfarin', function () {
            return Http::baseUrl('https://api.warfarin.wiki/v1');
        });
    }
}
