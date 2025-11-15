<?php

use App\Enums\FacilityType;
use App\Enums\Locale;
use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list all facilities', function () {
    $facility1 = Facility::factory()->create([
        'slug' => 'facility-1',
        'type' => FacilityType::BASIC_MACHINE,
    ]);

    $facility2 = Facility::factory()->create([
        'slug' => 'facility-2',
        'type' => FacilityType::ASSEMBLE_MACHINE,
    ]);

    $response = $this->getJson('/api/v1/facilities');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'slug' => 'facility-1',
            'type' => FacilityType::BASIC_MACHINE->value,
        ])
        ->assertJsonFragment([
            'slug' => 'facility-2',
            'type' => FacilityType::ASSEMBLE_MACHINE->value,
        ]);
});

it('can list facilities without authentication', function () {
    Facility::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/facilities');

    $response->assertSuccessful();
});

it('can filter facilities by type', function () {
    Facility::factory()->create([
        'type' => FacilityType::BASIC_MACHINE,
    ]);

    Facility::factory()->create([
        'type' => FacilityType::ASSEMBLE_MACHINE,
    ]);

    $response = $this->getJson('/api/v1/facilities?filter[type]='.FacilityType::BASIC_MACHINE->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'type' => FacilityType::BASIC_MACHINE->value,
        ]);
});

it('can filter facilities by slug', function () {
    Facility::factory()->create([
        'slug' => 'test-facility',
    ]);

    Facility::factory()->create([
        'slug' => 'other-facility',
    ]);

    $response = $this->getJson('/api/v1/facilities?filter[slug]=test-facility');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'slug' => 'test-facility',
        ]);
});

it('can show a single facility', function () {
    $facility = Facility::factory()->create([
        'slug' => 'test-facility',
        'type' => FacilityType::BASIC_MACHINE,
    ]);

    $response = $this->getJson("/api/v1/facilities/{$facility->slug}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $facility->id,
                'slug' => 'test-facility',
                'type' => FacilityType::BASIC_MACHINE->value,
                'type_display' => FacilityType::BASIC_MACHINE->displayName(),
            ],
        ]);
});

it('can show facility by slug using route model binding', function () {
    $facility = Facility::factory()->create([
        'slug' => 'test-facility',
    ]);

    $response = $this->getJson("/api/v1/facilities/{$facility->slug}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $facility->id,
                'slug' => 'test-facility',
            ],
        ]);
});

it('returns 404 when showing a non-existent facility', function () {
    $response = $this->getJson('/api/v1/facilities/non-existent-slug');

    $response->assertNotFound();
});

it('includes translatable fields in response', function () {
    $facility = Facility::factory()->create();
    $facility->setTranslation('name', Locale::ENGLISH->value, 'Test Facility');
    $facility->setTranslation('description', Locale::ENGLISH->value, 'Test Description');
    $facility->save();

    $response = $this->getJson("/api/v1/facilities/{$facility->slug}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'name',
                'description',
            ],
        ]);
});

it('includes range data in response', function () {
    $facility = Facility::factory()->create([
        'range' => [
            'width' => 5,
            'height' => 5,
            'depth' => 5,
            'x' => 0,
            'y' => 0,
            'z' => 0,
        ],
    ]);

    $response = $this->getJson("/api/v1/facilities/{$facility->slug}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'range' => [
                    'width' => 5,
                    'height' => 5,
                    'depth' => 5,
                    'x' => 0,
                    'y' => 0,
                    'z' => 0,
                ],
            ],
        ]);
});

it('can sort facilities by slug', function () {
    Facility::factory()->create(['slug' => 'z-facility']);
    Facility::factory()->create(['slug' => 'a-facility']);
    Facility::factory()->create(['slug' => 'm-facility']);

    $response = $this->getJson('/api/v1/facilities?sort=slug');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['slug'])->toBe('a-facility');
    expect($data[1]['slug'])->toBe('m-facility');
    expect($data[2]['slug'])->toBe('z-facility');
});

it('paginates facility results', function () {
    Facility::factory()->count(30)->create();

    $response = $this->getJson('/api/v1/facilities');

    $response->assertSuccessful()
        ->assertJsonCount(25, 'data')
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});
