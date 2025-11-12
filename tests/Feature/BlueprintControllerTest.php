<?php

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\Status;
use App\Enums\TagType;
use App\Models\Blueprint;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
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

    $response = $this->getJson('/api/v1/blueprints');

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
    $response = $this->postJson('/api/v1/blueprints', [
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

    $response = $this->postJson('/api/v1/blueprints', [
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

    $response = $this->postJson('/api/v1/blueprints', [
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
    $response = $this->postJson('/api/v1/blueprints', [
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
    $response = $this->postJson('/api/v1/blueprints', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'title', 'version']);
});

it('validates code is a string when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 123,
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('validates version is a valid enum when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => 'invalid-version',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['version']);
});

it('validates tags exist when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
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

    $response = $this->postJson('/api/v1/blueprints', [
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

    $response = $this->actingAs($otherUser)->getJson("/api/v1/blueprints/{$blueprint->id}");

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

    $response = $this->getJson("/api/v1/blueprints/{$blueprint->id}");

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

    $response = $this->getJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertForbidden();
});

it('can update own blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Old Title',
        'code' => 'OLDCODE',
    ]);

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
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

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
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

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
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

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
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
    $otherUser = User::factory()->regularUser()->create();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($otherUser)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertForbidden();
});

it('can update blueprint from another user as admin', function () {
    $admin = User::factory()->admin()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($admin)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'title' => 'Updated by Admin',
    ]);

    $response->assertSuccessful();
    $blueprint->refresh();
    expect($blueprint->title)->toBe('Updated by Admin');
});

it('can update blueprint from another user as moderator', function () {
    $moderator = User::factory()->moderator()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($moderator)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'title' => 'Updated by Moderator',
    ]);

    $response->assertSuccessful();
    $blueprint->refresh();
    expect($blueprint->title)->toBe('Updated by Moderator');
});

it('can delete own blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertNoContent();

    $this->assertSoftDeleted('blueprints', [
        'id' => $blueprint->id,
    ]);
});

it('cannot delete blueprint from another user', function () {
    $otherUser = User::factory()->regularUser()->create();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($otherUser)->deleteJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertForbidden();
});

it('can delete blueprint from another user as admin', function () {
    $admin = User::factory()->admin()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($admin)->deleteJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('blueprints', [
        'id' => $blueprint->id,
    ]);
});

it('can delete blueprint from another user as moderator', function () {
    $moderator = User::factory()->moderator()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($moderator)->deleteJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('blueprints', [
        'id' => $blueprint->id,
    ]);
});

it('generates slug from title when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
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

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
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
    $response = $this->getJson('/api/v1/blueprints/non-existent-id');

    $response->assertNotFound();
});

it('can like a blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/like");

    $response->assertSuccessful()
        ->assertJson([
            'liked' => true,
            'likes_count' => 1,
        ]);

    $this->assertDatabaseHas('blueprint_likes', [
        'user_id' => $this->user->id,
        'blueprint_id' => $blueprint->id,
    ]);
});

it('can unlike a blueprint', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $blueprint->likes()->attach($this->user->id);

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/like");

    $response->assertSuccessful()
        ->assertJson([
            'liked' => false,
            'likes_count' => 0,
        ]);

    $this->assertDatabaseMissing('blueprint_likes', [
        'user_id' => $this->user->id,
        'blueprint_id' => $blueprint->id,
    ]);
});

it('can toggle like on a blueprint multiple times', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    // Like
    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/like");
    $response->assertSuccessful()->assertJson(['liked' => true, 'likes_count' => 1]);

    // Unlike
    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/like");
    $response->assertSuccessful()->assertJson(['liked' => false, 'likes_count' => 0]);

    // Like again
    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/like");
    $response->assertSuccessful()->assertJson(['liked' => true, 'likes_count' => 1]);
});

it('includes likes_count and is_liked in blueprint response', function () {
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
    ]);

    // Like the blueprint
    $blueprint->likes()->attach($this->user->id);

    $response = $this->getJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'likes_count' => 1,
                'is_liked' => true,
            ],
        ]);
});

it('shows is_liked as false when user has not liked blueprint', function () {
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
    ]);

    $response = $this->getJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'likes_count' => 0,
                'is_liked' => false,
            ],
        ]);
});

it('can track a blueprint copy', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/copy");

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Copy tracked successfully',
        ])
        ->assertJsonStructure([
            'copies_count',
        ]);

    $this->assertDatabaseHas('blueprint_copies', [
        'user_id' => $this->user->id,
        'blueprint_id' => $blueprint->id,
    ]);
});

it('rate limits blueprint copy to once per day per user', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    // First copy
    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/copy");
    $response->assertSuccessful();

    // Second copy attempt within 24 hours
    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/copy");
    $response->assertStatus(429)
        ->assertJson([
            'message' => 'You have already copied this blueprint today. Please try again tomorrow.',
        ]);
});

it('includes copies_count in blueprint response', function () {
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    // Create a copy
    $blueprint->copies()->create([
        'user_id' => $this->user->id,
        'ip_address' => '127.0.0.1',
        'copied_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $blueprint->id,
                'copies_count' => 1,
            ],
        ]);
});

it('includes likes_count and copies_count in blueprint list', function () {
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
    ]);

    // Add a like
    $blueprint->likes()->attach($this->user->id);

    // Add a copy
    $blueprint->copies()->create([
        'user_id' => $this->user->id,
        'ip_address' => '127.0.0.1',
        'copied_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/blueprints');

    $response->assertSuccessful()
        ->assertJsonFragment([
            'id' => $blueprint->id,
            'likes_count' => 1,
            'copies_count' => 1,
        ]);
});

it('cannot like a blueprint without authentication', function () {
    $blueprint = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    // Remove authentication
    $this->actingAsGuest();

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/like");

    $response->assertUnauthorized();
});

it('cannot copy a blueprint without authentication', function () {
    $blueprint = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    // Remove authentication
    $this->actingAsGuest();

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/copy");

    $response->assertUnauthorized();
});
