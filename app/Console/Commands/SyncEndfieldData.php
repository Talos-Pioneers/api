<?php

namespace App\Console\Commands;

use App\Enums\FacilityType;
use App\Enums\ItemType;
use App\Enums\Locale;
use App\Models\Facility;
use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
    public function handle(): void
    {
        $localeStrings = ['en', 'cn', 'jp', 'kr', 'tc'];
        $resources = ['facilities', 'items'];

        foreach ($localeStrings as $localeString) {
            $locale = Locale::fromString($localeString);

            if ($locale === null) {
                $this->warn("Skipping unknown locale: {$localeString}");

                continue;
            }

            foreach ($resources as $resource) {
                $this->handleResource($resource, $localeString, $locale);
            }
        }
    }

    public function handleResource(string $resource, string $localeString, Locale $locale): void
    {
        $this->info("Processing {$resource} for locale {$locale->value}...");

        $url = vsprintf('%s/%s', [$localeString, $resource]);

        try {
            $response = Http::warfarin()->get($url);

            if (! $response->successful()) {
                $this->error("Failed to fetch {$url}: {$response->status()}");

                return;
            }

            $indexData = $response->json();

            if (! is_array($indexData)) {
                $this->error("Invalid response format for {$url}");

                return;
            }

            $slugs = collect($indexData)->pluck('slug')->filter();

            $this->info("Found {$slugs->count()} {$resource} to sync");

            $bar = $this->output->createProgressBar($slugs->count());
            $bar->start();

            foreach ($slugs as $slug) {
                $this->syncResource($resource, $slug, $localeString, $locale, $indexData);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("Error processing {$resource} for locale {$locale->value}: {$e->getMessage()}");
        }
    }

    protected function syncResource(string $resource, string $slug, string $localeString, Locale $locale, array $indexData): void
    {
        $indexItem = collect($indexData)->firstWhere('slug', $slug);

        if ($indexItem === null) {
            return;
        }

        $url = vsprintf('%s/%s/%s', [$localeString, $resource, $slug]);

        try {
            $response = Http::warfarin()->get($url);

            if (! $response->successful()) {
                $this->warn("Failed to fetch {$url}: {$response->status()}");

                return;
            }

            $showData = $response->json();

            if (! is_array($showData)) {
                $this->warn("Invalid response format for {$url}");

                return;
            }

            if ($resource === 'facilities') {
                $this->syncFacility($slug, $locale, $indexItem, $showData);
            } else {
                $this->syncItem($slug, $locale, $indexItem, $showData);
            }
        } catch (\Exception $e) {
            $this->warn("Error syncing {$resource} {$slug}: {$e->getMessage()}");
        }
    }

    protected function syncFacility(string $slug, Locale $locale, array $indexData, array $showData): void
    {
        $summary = $showData['summary'] ?? [];
        $factoryBuildingTable = $showData['factoryBuildingTable'] ?? [];

        $typeString = $summary['quickBarType'] ?? null;
        $facilityType = $typeString ? FacilityType::tryFrom($typeString) : null;

        if ($facilityType === null && $typeString !== null) {
            $this->warn("Unknown facility type: {$typeString} for slug: {$slug}");
        }

        $facility = Facility::firstOrNew(
            ['slug' => $slug],
            [
                'icon' => $indexData['icon'] ?? '',
                'type' => $facilityType,
                'range' => $factoryBuildingTable['range'] ?? [],
            ]
        );

        if (isset($summary['name'])) {
            $facility->setTranslation('name', $locale->value, $summary['name']);
        }

        if (isset($factoryBuildingTable['desc'])) {
            $facility->setTranslation('description', $locale->value, $factoryBuildingTable['desc']);
        }

        $facility->save();
    }

    protected function syncItem(string $slug, Locale $locale, array $indexData, array $showData): void
    {
        $summary = $showData['summary'] ?? [];
        $itemTable = $showData['itemTable'] ?? [];

        $typeString = $summary['typeName'] ?? null;
        $itemType = null;

        if ($typeString !== null) {
            $typeSnakeCase = Str::of($typeString)->snake()->lower()->toString();
            $itemType = ItemType::tryFrom($typeSnakeCase);

            if ($itemType === null) {
                $this->warn("Unknown item type: {$typeString} (snake_case: {$typeSnakeCase}) for slug: {$slug}");
            }
        }

        $item = Item::firstOrNew(
            ['slug' => $slug],
            [
                'icon' => $indexData['iconId'] ?? '',
                'type' => $itemType,
                'output_facility_craft_table' => $showData['outFactoryMachineCraftTable'] ?? [],
            ]
        );

        if (isset($summary['name'])) {
            $item->setTranslation('name', $locale->value, $summary['name']);
        }

        if (isset($itemTable['desc'])) {
            $item->setTranslation('description', $locale->value, $itemTable['desc']);
        }

        $item->save();
    }
}
