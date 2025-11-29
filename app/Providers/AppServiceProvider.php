<?php

namespace App\Providers;

use App\Listeners\AutoApproveComment;
use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\Comment;
use App\Models\Report;
use App\Policies\BlueprintCollectionPolicy;
use App\Policies\BlueprintPolicy;
use App\Policies\CommentPolicy;
use App\Policies\ReportPolicy;
use App\Policies\TagPolicy;
use BeyondCode\Comments\Events\CommentAdded;
use Illuminate\Support\Facades\Event;
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
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);

        Event::listen(CommentAdded::class, AutoApproveComment::class);
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('discord', \SocialiteProviders\Discord\Provider::class);
        });

        Http::macro('warfarin', function () {
            return Http::baseUrl('https://cbt3-api.warfarin.wiki/v1');
        });
    }
}
