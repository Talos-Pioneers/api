<?php

use App\Enums\ItemType;
use App\Enums\Locale;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list all items', function () {
    $item1 = Item::factory()->create([
        'slug' => 'item-1',
        'type' => ItemType::MATERIAL,
    ]);

    $item2 = Item::factory()->create([
        'slug' => 'item-2',
        'type' => ItemType::CONSUMABLE,
    ]);

    $response = $this->getJson('/api/v1/items');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'slug' => 'item-1',
            'type' => ItemType::MATERIAL->value,
        ])
        ->assertJsonFragment([
            'slug' => 'item-2',
            'type' => ItemType::CONSUMABLE->value,
        ]);
});

it('can list items without authentication', function () {
    Item::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/items');

    $response->assertSuccessful();
});

it('can filter items by type', function () {
    Item::factory()->create([
        'type' => ItemType::MATERIAL,
    ]);

    Item::factory()->create([
        'type' => ItemType::CURRENCY,
    ]);

    $response = $this->getJson('/api/v1/items?filter[type]='.ItemType::MATERIAL->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'type' => ItemType::MATERIAL->value,
        ]);
});

it('can filter items by slug', function () {
    Item::factory()->create([
        'slug' => 'test-item',
    ]);

    Item::factory()->create([
        'slug' => 'other-item',
    ]);

    $response = $this->getJson('/api/v1/items?filter[slug]=test-item');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'slug' => 'test-item',
        ]);
});

it('can show a single item', function () {
    $item = Item::factory()->create([
        'slug' => 'test-item',
        'type' => ItemType::MATERIAL,
    ]);

    $response = $this->getJson("/api/v1/items/{$item->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $item->id,
                'slug' => 'test-item',
                'type' => ItemType::MATERIAL->value,
                'type_display' => ItemType::MATERIAL->displayName(),
            ],
        ]);
});

it('can show item by slug using route model binding', function () {
    $item = Item::factory()->create([
        'slug' => 'test-item',
    ]);

    $response = $this->getJson("/api/v1/items/{$item->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $item->id,
                'slug' => 'test-item',
            ],
        ]);
});

it('returns 404 when showing a non-existent item', function () {
    $response = $this->getJson('/api/v1/items/non-existent-slug');

    $response->assertNotFound();
});

it('includes translatable fields in response', function () {
    $item = Item::factory()->create();
    $item->setTranslation('name', Locale::ENGLISH->value, 'Test Item');
    $item->setTranslation('description', Locale::ENGLISH->value, 'Test Description');
    $item->save();

    $response = $this->getJson("/api/v1/items/{$item->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'name',
                'description',
            ],
        ]);
});

it('includes output_facility_craft_table in response', function () {
    $item = Item::factory()->create([
        'output_facility_craft_table' => [
            [
                'id' => 'craft-1',
                'machineId' => 'machine-1',
                'ingredients' => [],
                'outcomes' => [],
            ],
        ],
    ]);

    $response = $this->getJson("/api/v1/items/{$item->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'output_facility_craft_table' => [
                    [
                        'id' => 'craft-1',
                        'machineId' => 'machine-1',
                        'ingredients' => [],
                        'outcomes' => [],
                    ],
                ],
            ],
        ]);
});

it('can sort items by slug', function () {
    Item::factory()->create(['slug' => 'z-item']);
    Item::factory()->create(['slug' => 'a-item']);
    Item::factory()->create(['slug' => 'm-item']);

    $response = $this->getJson('/api/v1/items?sort=slug');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['slug'])->toBe('a-item');
    expect($data[1]['slug'])->toBe('m-item');
    expect($data[2]['slug'])->toBe('z-item');
});
