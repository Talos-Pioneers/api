<?php

use App\Enums\ItemType;
use App\Enums\Locale;
use App\Jobs\SyncEndfieldResourceJob;
use App\Models\Facility;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

uses(RefreshDatabase::class);

it('dispatches a chained job for each locale and resource pair', function () {
    Bus::fake();

    $this->artisan('sync:endfield-data')
        ->expectsOutput('Dispatched endfield sync chain (28 jobs).')
        ->assertSuccessful();

    $localeStrings = ['en', 'cn', 'jp', 'kr', 'tc', 'es', 'ru', 'th', 'fr', 'de', 'id', 'it', 'br', 'vn'];
    $resources = ['items', 'facilities'];

    $expectedJobs = [];

    foreach ($localeStrings as $localeString) {
        foreach ($resources as $resource) {
            $expectedJobs[] = new SyncEndfieldResourceJob($resource, $localeString);
        }
    }

    Bus::assertChained($expectedJobs);
});

it('syncs an item from the warfarin api', function () {
    Http::fake([
        'https://api.warfarin.wiki/v1/en/items' => Http::response([
            'meta' => ['lang' => 'en', 'type' => 'item', 'count' => 1],
            'data' => [
                ['slug' => 'test-item', 'type' => 8, 'iconId' => 'icon_test'],
            ],
        ]),
        'https://api.warfarin.wiki/v1/en/items/test-item' => Http::response([
            'meta' => ['slug' => 'test-item', 'name' => 'Test Item'],
            'data' => [
                'itemTable' => ['desc' => 'Test description'],
                'outFactoryMachineCraftTable' => [],
            ],
            'refs' => [
                'itemTypeTable' => ['name' => 'Material'],
            ],
        ]),
    ]);

    SyncEndfieldResourceJob::dispatchSync('items', 'en');

    $item = Item::query()->where('slug', 'test-item')->first();

    expect($item)->not->toBeNull()
        ->and($item->icon)->toBe('icon_test')
        ->and($item->type)->toBe(ItemType::MATERIAL)
        ->and($item->getTranslation('name', Locale::ENGLISH->value))->toBe('Test Item')
        ->and($item->getTranslation('description', Locale::ENGLISH->value))->toBe('Test description');
});

it('syncs a facility from the warfarin api', function () {
    Http::fake([
        'https://api.warfarin.wiki/v1/en/facilities' => Http::response([
            'meta' => ['lang' => 'en', 'type' => 'facility', 'count' => 1],
            'data' => [
                ['slug' => 'test-facility', 'icon' => 'icon_facility'],
            ],
        ]),
        'https://api.warfarin.wiki/v1/en/facilities/test-facility' => Http::response([
            'meta' => ['slug' => 'test-facility', 'name' => 'Test Facility'],
            'data' => [
                'factoryBuildingTable' => [
                    'quickBarType' => 'basic_machine',
                    'desc' => 'Facility description',
                    'range' => [1, 2],
                ],
            ],
        ]),
    ]);

    SyncEndfieldResourceJob::dispatchSync('facilities', 'en');

    $facility = Facility::query()->where('slug', 'test-facility')->first();

    expect($facility)->not->toBeNull()
        ->and($facility->icon)->toBe('icon_facility')
        ->and($facility->getTranslation('name', Locale::ENGLISH->value))->toBe('Test Facility')
        ->and($facility->getTranslation('description', Locale::ENGLISH->value))->toBe('Facility description');
});

it('registers the sync command on a weekly monday schedule at 02:00', function () {
    $event = collect(Schedule::events())->first(
        fn ($event) => str_contains($event->command ?? '', 'sync:endfield-data')
    );

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 2 * * 1');
});
