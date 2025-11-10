<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SyncEndfieldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:endfield-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs Endfield data using warfarin.wiki\'s api';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locales = ['en', 'cn', 'jp', 'kr', 'tc'];
        $resources = ['facilities', 'items'];

        foreach ($locales as $locale) {
            foreach ($resources as $resource) {
                $this->handleResource($resource, $locale);
            }
        }
    }

    public function handleResource($resource, $locale)
    {
        $url = vsprintf('%s/%s', [$locale, $resource]);
        $resources = Cache::rememberForever($url, function () use ($url) {
            $response = Http::warfarin()->get($url);

            return $response->json();
        });
        $resources = collect($resources)->pluck('slug');
        $this->info($resources);
        foreach ($resources as $resource_item) {
            $url = vsprintf('%s/%s/%s', [$locale, $resource, $resource_item]);
            $this->info($url);
            Cache::rememberForever($url, function () use ($url) {
                $response = Http::warfarin()->get($url);

                return $response->json();
            });
        }
    }
}
