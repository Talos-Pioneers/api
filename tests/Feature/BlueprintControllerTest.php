<?php

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\ServerRegion;
use App\Enums\Status;
use App\Enums\TagType;
use App\Models\Blueprint;
use App\Models\Facility;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
    $this->actingAs($this->user);
    Config::set('services.auto_mod.enabled', false);
    Config::set('services.openai.api_key', 'test-key');
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
    $facility1 = Facility::factory()->create(['slug' => 'building-1']);
    $facility2 = Facility::factory()->create(['slug' => 'building-2']);
    $itemInput1 = Item::factory()->create(['slug' => 'iron-ore']);
    $itemInput2 = Item::factory()->create(['slug' => 'copper-ore']);
    $itemOutput1 = Item::factory()->create(['slug' => 'iron-plate']);
    $itemOutput2 = Item::factory()->create(['slug' => 'copper-plate']);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test Blueprint',
        'version' => GameVersion::CBT_3->value,
        'description' => 'A test blueprint',
        'status' => Status::DRAFT->value,
        'region' => Region::VALLEY_IV->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
        'facilities' => [
            ['id' => $facility1->id, 'quantity' => 2],
            ['id' => $facility2->id, 'quantity' => 1],
        ],
        'item_inputs' => [
            ['id' => $itemInput1->id, 'quantity' => 10],
            ['id' => $itemInput2->id, 'quantity' => 5],
        ],
        'item_outputs' => [
            ['id' => $itemOutput1->id, 'quantity' => 8],
            ['id' => $itemOutput2->id, 'quantity' => 4],
        ],
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
            ],
        ]);

    $blueprint = Blueprint::where('code', 'EFE750a2A78o53Ela')->first();
    expect($blueprint->facilities)->toHaveCount(2);
    expect($blueprint->itemInputs)->toHaveCount(2);
    expect($blueprint->itemOutputs)->toHaveCount(2);
    expect($blueprint->facilities->firstWhere('id', $facility1->id)->pivot->quantity)->toBe(2);
    expect($blueprint->itemInputs->firstWhere('id', $itemInput1->id)->pivot->quantity)->toBe(10);

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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
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

it('sets status to review when title fails moderation', function () {
    Config::set('services.auto_mod.enabled', true);

    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    // Mock OpenAI client in container
    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Inappropriate Title',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(Status::REVIEW->value);
});

it('sets status to review when description fails moderation', function () {
    Config::set('services.auto_mod.enabled', true);

    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['harassment' => true],
                    'category_scores' => ['harassment' => 0.9],
                ],
            ],
        ]),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Safe Title',
        'description' => 'Inappropriate description content',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(Status::REVIEW->value);
});

it('allows blueprint creation when content passes moderation', function () {
    Config::set('services.auto_mod.enabled', true);

    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Safe Blueprint Title',
        'description' => 'A safe and appropriate description',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
});

it('sets status to review when title fails moderation during update', function () {
    Config::set('services.auto_mod.enabled', true);

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'title' => 'Inappropriate Updated Title',
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(Status::REVIEW->value);
});

it('allows blueprint update when content passes moderation', function () {
    Config::set('services.auto_mod.enabled', true);

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'title' => 'Safe Updated Title',
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
});

it('handles moderation api errors gracefully during creation', function () {
    Config::set('services.auto_mod.enabled', true);

    $response = new \GuzzleHttp\Psr7\Response(500, [], json_encode([
        'error' => [
            'message' => 'API error',
            'type' => 'invalid_request_error',
            'code' => null,
        ],
    ]));

    $client = new \OpenAI\Testing\ClientFake([
        new \OpenAI\Exceptions\ErrorException([
            'message' => 'API error',
            'type' => 'invalid_request_error',
            'code' => null,
        ], $response),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    // Should not fail, but log warning and allow creation
    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test Title',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    // API errors should be logged but not block creation
    $response->assertSuccessful();
});

it('sets status to review when image fails moderation', function () {
    Config::set('services.auto_mod.enabled', true);

    $image = UploadedFile::fake()->image('inappropriate.jpg');

    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => true,
                    'categories' => ['sexual' => true],
                    'category_scores' => ['sexual' => 0.95],
                ],
            ],
        ]),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Safe Title',
        'description' => 'Safe description',
        'version' => GameVersion::CBT_3->value,
        'gallery' => [$image],
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(Status::REVIEW->value);
});

it('allows blueprint creation when images pass moderation', function () {
    Config::set('services.auto_mod.enabled', true);

    $image1 = UploadedFile::fake()->image('safe1.jpg');
    $image2 = UploadedFile::fake()->image('safe2.jpg');

    // Title, description, image1, image2 = 4 results needed
    $client = new \OpenAI\Testing\ClientFake([
        \OpenAI\Responses\Moderations\CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    $this->app->bind(\OpenAI\Client::class, fn () => $client);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Safe Blueprint',
        'description' => 'Safe description',
        'version' => GameVersion::CBT_3->value,
        'gallery' => [$image1, $image2],
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
});

it('can filter blueprints by region', function () {
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'region' => Region::VALLEY_IV,
    ]);

    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'region' => Region::WULING,
    ]);

    $response = $this->getJson('/api/v1/blueprints?filter[region]='.Region::VALLEY_IV->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'region' => Region::VALLEY_IV->value,
        ]);
});

it('can filter blueprints by server_region', function () {
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'server_region' => ServerRegion::AMERICA_EUROPE,
    ]);

    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'server_region' => ServerRegion::ASIA,
    ]);

    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'server_region' => ServerRegion::CN,
    ]);

    $response = $this->getJson('/api/v1/blueprints?filter[server_region]='.ServerRegion::AMERICA_EUROPE->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'server_region' => ServerRegion::AMERICA_EUROPE->value,
        ]);
});

it('can filter blueprints by version', function () {
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'version' => GameVersion::CBT_3,
    ]);

    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'version' => GameVersion::CBT_3,
    ]);

    $response = $this->getJson('/api/v1/blueprints?filter[version]='.GameVersion::CBT_3->value);

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'version' => GameVersion::CBT_3->value,
        ]);
});

it('can filter blueprints by is_anonymous', function () {
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'is_anonymous' => true,
    ]);

    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'is_anonymous' => false,
    ]);

    $response = $this->getJson('/api/v1/blueprints?filter[is_anonymous]=1');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'creator' => null,
        ]);
});

it('can filter blueprints by author_id', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $user1->id,
        'status' => Status::PUBLISHED,
        'is_anonymous' => false,
    ]);

    Blueprint::factory()->create([
        'creator_id' => $user2->id,
        'status' => Status::PUBLISHED,
        'is_anonymous' => false,
    ]);

    $response = $this->getJson("/api/v1/blueprints?filter[author_id]={$user1->id}");

    $response->assertSuccessful();
    $data = $response->json('data');

    // Should include the blueprint we created
    $foundBlueprint = collect($data)->firstWhere('id', $blueprint1->id);
    expect($foundBlueprint)->not->toBeNull();
    expect($foundBlueprint['creator']['id'])->toBe($user1->id);
});

it('excludes anonymous blueprints when filtering by author_id', function () {
    $user = User::factory()->create();

    Blueprint::factory()->create([
        'creator_id' => $user->id,
        'status' => Status::PUBLISHED,
        'is_anonymous' => true,
    ]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $user->id,
        'status' => Status::PUBLISHED,
        'is_anonymous' => false,
    ]);

    $response = $this->getJson("/api/v1/blueprints?filter[author_id]={$user->id}");

    $response->assertSuccessful();
    $data = $response->json('data');

    // Should include the non-anonymous blueprint we created
    $foundBlueprint = collect($data)->firstWhere('id', $blueprint2->id);
    expect($foundBlueprint)->not->toBeNull();
    expect($foundBlueprint['creator']['id'])->toBe($user->id);

    // Should not include the anonymous blueprint
    $anonymousBlueprint = collect($data)->firstWhere('id', function ($id) use ($user) {
        return Blueprint::where('id', $id)
            ->where('creator_id', $user->id)
            ->where('is_anonymous', true)
            ->exists();
    });
    expect($anonymousBlueprint)->toBeNull();
});

it('can filter blueprints by tags', function () {
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

    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint1->syncTags([$tag1]);

    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint2->syncTags([$tag2]);

    $response = $this->getJson("/api/v1/blueprints?filter[tags.id]={$tag1->id}");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ]);
});

it('can filter blueprints by multiple tags', function () {
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

    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint1->syncTags([$tag1, $tag2]);

    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint2->syncTags([$tag1]);

    $response = $this->getJson("/api/v1/blueprints?filter[tags.id]={$tag1->id},{$tag2->id}");

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can sort blueprints by title', function () {
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'title' => 'Z Blueprint',
    ]);
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'title' => 'A Blueprint',
    ]);
    Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'title' => 'M Blueprint',
    ]);

    $response = $this->getJson('/api/v1/blueprints?sort=title');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['title'])->toBe('A Blueprint');
    expect($data[1]['title'])->toBe('M Blueprint');
    expect($data[2]['title'])->toBe('Z Blueprint');
});

it('can sort blueprints by created_at', function () {
    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'created_at' => now()->subDays(2),
    ]);
    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'created_at' => now()->subDays(1),
    ]);
    $blueprint3 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/blueprints?sort=created_at');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($blueprint1->id);
    expect($data[1]['id'])->toBe($blueprint2->id);
    expect($data[2]['id'])->toBe($blueprint3->id);
});

it('can sort blueprints by updated_at', function () {
    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'updated_at' => now()->subDays(2),
    ]);
    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'updated_at' => now()->subDays(1),
    ]);
    $blueprint3 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/blueprints?sort=updated_at');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($blueprint1->id);
    expect($data[1]['id'])->toBe($blueprint2->id);
    expect($data[2]['id'])->toBe($blueprint3->id);
});

it('defaults to sorting by created_at', function () {
    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'created_at' => now()->subDays(2),
    ]);
    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'created_at' => now()->subDays(1),
    ]);
    $blueprint3 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/blueprints');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($blueprint3->id);
    expect($data[1]['id'])->toBe($blueprint2->id);
    expect($data[2]['id'])->toBe($blueprint1->id);
});

it('paginates blueprint results', function () {
    Blueprint::factory()->count(30)->create([
        'status' => Status::PUBLISHED,
    ]);

    $response = $this->getJson('/api/v1/blueprints');

    $response->assertSuccessful()
        ->assertJsonCount(25, 'data')
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('can list blueprints without authentication', function () {
    Blueprint::factory()->count(3)->create([
        'status' => Status::PUBLISHED,
    ]);

    $this->actingAsGuest();

    $response = $this->getJson('/api/v1/blueprints');

    $response->assertSuccessful();
});

it('can filter blueprints by facility slug', function () {
    $facility1 = Facility::factory()->create(['slug' => 'mining-facility']);
    $facility2 = Facility::factory()->create(['slug' => 'refining-facility']);

    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint1->facilities()->attach($facility1->id, ['quantity' => 2]);

    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint2->facilities()->attach($facility2->id, ['quantity' => 1]);

    $response = $this->getJson('/api/v1/blueprints?filter[facility]=mining-facility');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('can filter blueprints by item input slug', function () {
    $item1 = Item::factory()->create(['slug' => 'iron-ore']);
    $item2 = Item::factory()->create(['slug' => 'copper-ore']);

    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint1->itemInputs()->attach($item1->id, ['quantity' => 10]);

    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint2->itemInputs()->attach($item2->id, ['quantity' => 5]);

    $response = $this->getJson('/api/v1/blueprints?filter[item_input]=iron-ore');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('can filter blueprints by item output slug', function () {
    $item1 = Item::factory()->create(['slug' => 'iron-plate']);
    $item2 = Item::factory()->create(['slug' => 'copper-plate']);

    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint1->itemOutputs()->attach($item1->id, ['quantity' => 8]);

    $blueprint2 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint2->itemOutputs()->attach($item2->id, ['quantity' => 4]);

    $response = $this->getJson('/api/v1/blueprints?filter[item_output]=iron-plate');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('validates facility id exists when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
        'facilities' => [
            ['id' => 999, 'quantity' => 1],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['facilities.0.id']);
});

it('validates item input id exists when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
        'item_inputs' => [
            ['id' => 999, 'quantity' => 1],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['item_inputs.0.id']);
});

it('validates item output id exists when creating a blueprint', function () {
    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
        'item_outputs' => [
            ['id' => 999, 'quantity' => 1],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['item_outputs.0.id']);
});

it('validates quantity is positive when creating a blueprint', function () {
    $facility = Facility::factory()->create();

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Test',
        'version' => GameVersion::CBT_3->value,
        'facilities' => [
            ['id' => $facility->id, 'quantity' => 0],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['facilities.0.quantity']);
});

it('can filter blueprints by multiple facilities', function () {
    $facility1 = Facility::factory()->create(['slug' => 'mining-facility']);
    $facility2 = Facility::factory()->create(['slug' => 'refining-facility']);

    $blueprint1 = Blueprint::factory()->create([
        'status' => Status::PUBLISHED,
    ]);
    $blueprint1->facilities()->attach($facility1->id, ['quantity' => 2]);
    $blueprint1->facilities()->attach($facility2->id, ['quantity' => 1]);

    $response = $this->getJson('/api/v1/blueprints?filter[facility]=mining-facility,refining-facility');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ]);
});

it('includes facility and item icons in blueprint response', function () {
    $facility = Facility::factory()->create(['icon' => 'facility-icon']);
    $itemInput = Item::factory()->create(['icon' => 'item-input-icon']);
    $itemOutput = Item::factory()->create(['icon' => 'item-output-icon']);

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $blueprint->facilities()->attach($facility->id, ['quantity' => 2]);
    $blueprint->itemInputs()->attach($itemInput->id, ['quantity' => 3]);
    $blueprint->itemOutputs()->attach($itemOutput->id, ['quantity' => 4]);

    $response = $this->getJson("/api/v1/blueprints/{$blueprint->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.facilities.0.icon', 'facility-icon')
        ->assertJsonPath('data.item_inputs.0.icon', 'item-input-icon')
        ->assertJsonPath('data.item_outputs.0.icon', 'item-output-icon');
});
