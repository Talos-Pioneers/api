<?php

namespace App\Jobs;

use App\Enums\Locale;
use App\Services\EndfieldSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncEndfieldResourceJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $resource,
        public string $localeString,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EndfieldSyncService $sync): void
    {
        $locale = Locale::fromString($this->localeString);

        if ($locale === null) {
            Log::warning("Skipping unknown locale: {$this->localeString}");

            return;
        }

        $sync->syncResource($this->resource, $this->localeString, $locale);
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'endfield-sync',
            "{$this->resource}_{$this->localeString}",
        ];
    }
}
