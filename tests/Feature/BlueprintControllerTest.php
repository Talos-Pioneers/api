<?php

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\Status;
use App\Enums\TagType;
use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can list published blueprints', function () {
    $published1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Published Blueprint 1',
    ]);

    $published2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Published Blueprint 2',
    ]);

    $draft = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
        'title' => 'Draft Blueprint',
    ]);

    $response = $this->getJson('/api/blueprints');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $published1->id,
            'title' => 'Published Blueprint 1',
            'status' => Status::PUBLISHED->value,
        ])
        ->assertJsonFragment([
            'id' => $published2->id,
            'title' => 'Published Blueprint 2',
            'status' => Status::PUBLISHED->value,
        ])
        ->assertJsonMissing([
            'id' => $draft->id,
        ]);
});

it('can create a blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test Blueprint',
        'version' => GameVersion::CBT_3->value,
        'description' => 'A test blueprint',
        'status' => Status::DRAFT->value,
        'region' => Region::VALLEY_IV->value,
        'buildings' => ['building-1', 'building-2'],
        'item_inputs' => ['iron-ore', 'copper-ore'],
        'item_outputs' => ['iron-plate', 'copper-plate'],
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'code' => 'EFE750a2A78o53Ela',
                'title' => 'Test Blueprint',
                'slug' => 'test-blueprint',
                'version' => GameVersion::CBT_3->value,
                'description' => 'A test blueprint',
                'status' => Status::DRAFT->value,
                'region' => Region::VALLEY_IV->value,
                'buildings' => ['building-1', 'building-2'],
                'item_inputs' => ['iron-ore', 'copper-ore'],
                'item_outputs' => ['iron-plate', 'copper-plate'],
            ],
        ]);

    $this->assertDatabaseHas('blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test Blueprint',
        'slug' => 'test-blueprint',
        'creator_id' => $this->user->id,
    ]);
});

it('can create a blueprint with tags', function () {
    $tag1 = Tag::create([
        'name' => 'mining',
        'slug' => 'mining',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $tag2 = Tag::create([
        'name' => 'refining',
        'slug' => 'refining',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test Blueprint',
        'version' => GameVersion::CBT_3->value,
        'tags' => [$tag1->id, $tag2->id],
    ]);

    $response->assertSuccessful();

    $blueprint = Blueprint::where('code', 'EFE750a2A78o53Ela')->first();
    expect($blueprint->tags)->toHaveCount(2);
    expect($blueprint->tags->pluck('id')->toArray())->toContain($tag1->id, $tag2->id);
});

it('can create a blueprint with gallery images', function () {
    $image1 = UploadedFile::fake()->image('blueprint1.jpg', 800, 600);
    $image2 = UploadedFile::fake()->image('blueprint2.jpg', 800, 600);

    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test Blueprint',
        'version' => GameVersion::CBT_3->value,
        'gallery' => [$image1, $image2],
    ]);

    $response->assertSuccessful();

    $blueprint = Blueprint::where('code', 'EFE750a2A78o53Ela')->first();
    expect($blueprint->getMedia('gallery'))->toHaveCount(2);
});

it('can create an anonymous blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Anonymous Blueprint',
        'version' => GameVersion::CBT_3->value,
        'is_anonymous' => true,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'creator' => null,
            ],
        ]);

    $this->assertDatabaseHas('blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'is_anonymous' => true,
    ]);
});

it('validates required fields when creating a blueprint', function () {
    $response = $this->postJson('/api/blueprints', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'title', 'version']);
});

it('validates code is a string when creating a blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'code' => 123,
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('validates version is a valid enum when creating a blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => 'invalid-version',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['version']);
});

it('validates tags exist when creating a blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
        'tags' => [999, 1000],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['tags.0', 'tags.1']);
});

it('validates gallery files are images when creating a blueprint', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
        'gallery' => [$file],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['gallery.0']);
});

it('can show a published blueprint', function () {
    $otherUser = User::factory()->create();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
    ]);

    $response = $this->actingAs($otherUser)->getJson("/api/blueprints/{$blueprint->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'title' => $blueprint->title,
            ],
        ]);
});

it('can show own draft blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->getJson("/api/blueprints/{$blueprint->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'title' => $blueprint->title,
            ],
        ]);
});

it('cannot show draft blueprint from another user', function () {
    $otherUser = User::factory()->create();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->getJson("/api/blueprints/{$blueprint->id}");

    $response->assertForbidden();
});

it('can update own blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Old Title',
        'code' => 'OLDCODE',
    ]);

    $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
        'title' => 'New Title',
        'code' => 'NEWCODE',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'title' => 'New Title',
                'slug' => 'new-title',
                'code' => 'NEWCODE',
            ],
        ]);

    $blueprint->refresh();
    expect($blueprint->title)->toBe('New Title');
    expect($blueprint->code)->toBe('NEWCODE');
    expect($blueprint->slug)->toBe('new-title');
});

it('can update blueprint tags', function () {
    $tag1 = Tag::create([
        'name' => 'mining',
        'slug' => 'mining',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $tag2 = Tag::create([
        'name' => 'refining',
        'slug' => 'refining',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $tag3 = Tag::create([
        'name' => 'manufacturing',
        'slug' => 'manufacturing',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $blueprint->syncTags([$tag1, $tag2]);

    $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
        'tags' => [$tag2->id, $tag3->id],
    ]);

    $response->assertSuccessful();

    $blueprint->refresh();
    expect($blueprint->tags)->toHaveCount(2);
    expect($blueprint->tags->pluck('id')->toArray())->toContain($tag2->id, $tag3->id);
    expect($blueprint->tags->pluck('id')->toArray())->not->toContain($tag1->id);
});

it('can update blueprint gallery', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $image1 = UploadedFile::fake()->image('image1.jpg');
    $blueprint->addMedia($image1)->toMediaCollection('gallery');

    $image2 = UploadedFile::fake()->image('image2.jpg');
    $image3 = UploadedFile::fake()->image('image3.jpg');

    $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
        'gallery' => [$image2, $image3],
    ]);

    $response->assertSuccessful();

    $blueprint->refresh();
    // Original image plus 2 new ones
    expect($blueprint->getMedia('gallery')->count())->toBeGreaterThanOrEqual(2);
});

it('can update blueprint to anonymous', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'is_anonymous' => false,
    ]);

    $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
        'is_anonymous' => true,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'creator' => null,
            ],
        ]);

    $blueprint->refresh();
    expect($blueprint->is_anonymous)->toBeTrue();
});

it('cannot update blueprint from another user', function () {
    $otherUser = User::factory()->create();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($otherUser)->putJson("/api/blueprints/{$blueprint->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertForbidden();
});

it('can delete own blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/blueprints/{$blueprint->id}");

    $response->assertNoContent();

    $this->assertSoftDeleted('blueprints', [
        'id' => $blueprint->id,
    ]);
});

it('cannot delete blueprint from another user', function () {
    $otherUser = User::factory()->create();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($otherUser)->deleteJson("/api/blueprints/{$blueprint->id}");

    $response->assertForbidden();
});

it('generates slug from title when creating a blueprint', function () {
    $response = $this->postJson('/api/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'My Awesome Blueprint',
        'version' => GameVersion::CBT_3->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'title' => 'My Awesome Blueprint',
                'slug' => 'my-awesome-blueprint',
            ],
        ]);
});

it('generates slug from title when updating a blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Old Title',
        'slug' => 'old-title',
    ]);

    $response = $this->putJson("/api/blueprints/{$blueprint->id}", [
        'title' => 'Updated Blueprint Title',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'title' => 'Updated Blueprint Title',
                'slug' => 'updated-blueprint-title',
            ],
        ]);
});

it('returns 404 when showing non-existent blueprint', function () {
    $response = $this->getJson('/api/blueprints/non-existent-id');

    $response->assertNotFound();
});
