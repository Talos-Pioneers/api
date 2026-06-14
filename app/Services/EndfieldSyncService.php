<?php

namespace App\Services;

use App\Enums\FacilityType;
use App\Enums\ItemType;
use App\Enums\Locale;
use App\Models\Facility;
use App\Models\Item;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EndfieldSyncService
{
    public function syncResource(string $resource, string $localeString, Locale $locale): void
    {
        Log::info("Processing {$resource} for locale {$locale->value}...");

        $url = vsprintf('%s/%s', [$localeString, $resource]);

        try {
            $response = Http::warfarin()->get($url);

            if (! $response->successful()) {
                Log::error("Failed to fetch {$url}: {$response->status()}");

                return;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                Log::error("Invalid response format for {$url}");

                return;
            }

            $indexData = $this->indexEntries($payload);

            $slugs = collect($indexData)->pluck('slug')->filter();
            if ($resource === 'items') {
                $slugs = collect($indexData)
                    ->filter(fn (array $item): bool => isset($item['slug'], $item['type']))
                    ->whereIn('type', ItemType::craftableTypeIds())
                    ->pluck('slug');
            }

            Log::info("Found {$slugs->count()} {$resource} to sync for locale {$locale->value}");

            foreach ($slugs as $slug) {
                $this->syncResourceDetail($resource, $slug, $localeString, $locale, $indexData);
            }
        } catch (\Exception $e) {
            Log::error("Error processing {$resource} for locale {$locale->value}: {$e->getMessage()}");
        }
    }

    protected function syncResourceDetail(string $resource, string $slug, string $localeString, Locale $locale, array $indexData): void
    {
        $indexItem = collect($indexData)->firstWhere('slug', $slug);

        if ($indexItem === null) {
            return;
        }

        $url = vsprintf('%s/%s/%s', [$localeString, $resource, $slug]);

        try {
            $response = Http::warfarin()->get($url);

            if (! $response->successful()) {
                Log::warning("Failed to fetch {$url}: {$response->status()}");

                return;
            }

            $showData = $response->json();

            if (! is_array($showData)) {
                Log::warning("Invalid response format for {$url}");

                return;
            }

            if ($resource === 'facilities') {
                $this->syncFacility($slug, $locale, $indexItem, $showData);
            } else {
                $this->syncItem($slug, $locale, $indexItem, $showData);
            }
        } catch (\Exception $e) {
            Log::warning("Error syncing {$resource} {$slug}: {$e->getMessage()}");
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    protected function indexEntries(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data']) && array_is_list($payload['data'])) {
            return $payload['data'];
        }

        return array_is_list($payload) ? $payload : [];
    }

    protected function syncFacility(string $slug, Locale $locale, array $indexData, array $showData): void
    {
        $summary = $showData['summary'] ?? $showData['meta'] ?? [];
        $factoryBuildingTable = $showData['factoryBuildingTable']
            ?? $showData['data']['factoryBuildingTable']
            ?? [];

        $typeString = $factoryBuildingTable['quickBarType'] ?? null;
        $facilityType = $typeString ? FacilityType::tryFrom($typeString) : null;

        if ($facilityType === null && $typeString !== null) {
            Log::warning("Unknown facility type: {$typeString} for slug: {$slug}");
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
        $summary = $showData['summary'] ?? $showData['meta'] ?? [];
        $itemTypeTable = $showData['itemTypeTable']
            ?? $showData['refs']['itemTypeTable']
            ?? [];
        $itemTable = $showData['itemTable']
            ?? $showData['data']['itemTable']
            ?? [];

        $typeString = $itemTypeTable['name'] ?? null;
        $itemType = null;

        if ($typeString !== null && $locale === Locale::ENGLISH) {
            $typeSnakeCase = Str::of($typeString)->snake()->lower()->toString();
            $itemType = ItemType::tryFrom($typeSnakeCase);

            if ($itemType === null) {
                Log::warning("Unknown item type: {$typeString} (snake_case: {$typeSnakeCase}) for slug: {$slug}");
            }
        }

        $item = Item::firstOrNew(
            ['slug' => $slug],
            [
                'icon' => $indexData['iconId'] ?? '',
                'output_facility_craft_table' => $showData['outFactoryMachineCraftTable']
                    ?? $showData['data']['outFactoryMachineCraftTable']
                    ?? [],
            ]
        );
        if ($locale === Locale::ENGLISH) {
            $item->type = $itemType;
        }

        if (isset($summary['name'])) {
            $item->setTranslation('name', $locale->value, $summary['name']);
        }

        if (isset($itemTable['desc'])) {
            $item->setTranslation('description', $locale->value, $itemTable['desc']);
        }

        $item->save();
    }
}
