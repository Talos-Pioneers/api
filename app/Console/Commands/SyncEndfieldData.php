<?php

namespace App\Console\Commands;

use App\Enums\Locale;
use App\Jobs\SyncEndfieldResourceJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

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
     * @var array<int, string>
     */
    protected array $localeStrings = ['en', 'cn', 'jp', 'kr', 'tc', 'es', 'ru', 'th', 'fr', 'de', 'id', 'it', 'br', 'vn'];

    /**
     * @var array<int, string>
     */
    protected array $resources = ['items', 'facilities'];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $jobs = [];

        foreach ($this->localeStrings as $localeString) {
            if (Locale::fromString($localeString) === null) {
                $this->warn("Skipping unknown locale: {$localeString}");

                continue;
            }

            foreach ($this->resources as $resource) {
                $jobs[] = new SyncEndfieldResourceJob($resource, $localeString);
            }
        }

        Bus::chain($jobs)->dispatch();

        $this->info('Dispatched endfield sync chain ('.count($jobs).' jobs).');
    }
}
