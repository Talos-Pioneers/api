<?php

use App\Enums\Status;
use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
    $this->actingAs($this->user);
});

it('can list published collections', function () {
    $published1 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Published Collection 1',
    ]);

    $published2 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Published Collection 2',
    ]);

    $draft = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
        'title' => 'Private Collection',
    ]);

    $response = $this->getJson('/api/v1/collections');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $published1->id,
            'title' => 'Published Collection 1',
            'status' => Status::PUBLISHED->value,
        ])
        ->assertJsonFragment([
            'id' => $published2->id,
            'title' => 'Published Collection 2',
            'status' => Status::PUBLISHED->value,
        ])
        ->assertJsonMissing([
            'id' => $draft->id,
        ]);
});

it('can create a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'My Collection',
        'description' => 'A test collection',
        'status' => Status::DRAFT->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'title' => 'My Collection',
                'slug' => 'my-collection',
                'description' => 'A test collection',
                'status' => Status::DRAFT->value,
            ],
        ]);

    $this->assertDatabaseHas('blueprint_collections', [
        'title' => 'My Collection',
        'slug' => 'my-collection',
        'creator_id' => $this->user->id,
    ]);
});

it('can create a collection with blueprints', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Collection with Blueprints',
        'blueprints' => [$blueprint1->id, $blueprint2->id],
    ]);

    $response->assertSuccessful();

    $collection = BlueprintCollection::where('title', 'Collection with Blueprints')->first();
    expect($collection->blueprints)->toHaveCount(2);
    expect($collection->blueprints->pluck('id')->toArray())->toContain($blueprint1->id, $blueprint2->id);
});

it('can create a public collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Public Collection',
        'status' => Status::PUBLISHED->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'title' => 'Public Collection',
                'status' => Status::PUBLISHED->value,
            ],
        ]);

    $this->assertDatabaseHas('blueprint_collections', [
        'title' => 'Public Collection',
        'status' => Status::PUBLISHED->value,
    ]);
});

it('can create an anonymous collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Anonymous Collection',
        'is_anonymous' => true,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'creator' => null,
            ],
        ]);

    $this->assertDatabaseHas('blueprint_collections', [
        'title' => 'Anonymous Collection',
        'is_anonymous' => true,
    ]);
});

it('validates required fields when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('validates title is a string when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 123,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

it('validates status is a valid enum when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Test Collection',
        'status' => 'invalid-status',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('validates blueprints exist when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Test Collection',
        'blueprints' => [999, 1000],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['blueprints.0', 'blueprints.1']);
});

it('can show a published collection', function () {
    $otherUser = User::factory()->create();

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
    ]);

    $response = $this->getJson("/api/v1/collections/{$collection->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $collection->id,
                'title' => $collection->title,
            ],
        ]);
});

it('can show own private collection', function () {
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->getJson("/api/v1/collections/{$collection->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $collection->id,
                'title' => $collection->title,
            ],
        ]);
});

it('cannot show private collection from another user', function () {
    $otherUser = User::factory()->create();

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->getJson("/api/v1/collections/{$collection->id}");

    $response->assertForbidden();
});

it('can update own collection', function () {
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Old Title',
        'description' => 'Old Description',
    ]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'title' => 'New Title',
        'description' => 'New Description',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $collection->id,
                'title' => 'New Title',
                'slug' => 'new-title',
                'description' => 'New Description',
            ],
        ]);

    $collection->refresh();
    expect($collection->title)->toBe('New Title');
    expect($collection->description)->toBe('New Description');
    expect($collection->slug)->toBe('new-title');
});

it('can update collection blueprints', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $blueprint3 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $collection->blueprints()->sync([$blueprint1->id, $blueprint2->id]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'blueprints' => [$blueprint2->id, $blueprint3->id],
    ]);

    $response->assertSuccessful();

    $collection->refresh();
    expect($collection->blueprints)->toHaveCount(2);
    expect($collection->blueprints->pluck('id')->toArray())->toContain($blueprint2->id, $blueprint3->id);
    expect($collection->blueprints->pluck('id')->toArray())->not->toContain($blueprint1->id);
});

it('can update collection to public', function () {
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'status' => Status::PUBLISHED->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $collection->id,
                'status' => Status::PUBLISHED->value,
            ],
        ]);

    $collection->refresh();
    expect($collection->status)->toBe(Status::PUBLISHED);
});

it('can update collection to anonymous', function () {
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'is_anonymous' => false,
    ]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'is_anonymous' => true,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'creator' => null,
            ],
        ]);

    $collection->refresh();
    expect($collection->is_anonymous)->toBeTrue();
});

it('cannot update collection from another user', function () {
    $otherUser = User::factory()->regularUser()->create();

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $otherUser->id,
    ]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertForbidden();
});

it('can update collection from another user as admin', function () {
    $admin = User::factory()->admin()->create();
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($admin)->putJson("/api/v1/collections/{$collection->id}", [
        'title' => 'Updated by Admin',
    ]);

    $response->assertSuccessful();
    $collection->refresh();
    expect($collection->title)->toBe('Updated by Admin');
});

it('can update collection from another user as moderator', function () {
    $moderator = User::factory()->moderator()->create();
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($moderator)->putJson("/api/v1/collections/{$collection->id}", [
        'title' => 'Updated by Moderator',
    ]);

    $response->assertSuccessful();
    $collection->refresh();
    expect($collection->title)->toBe('Updated by Moderator');
});

it('can delete own collection', function () {
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/v1/collections/{$collection->id}");

    $response->assertNoContent();

    $this->assertSoftDeleted('blueprint_collections', [
        'id' => $collection->id,
    ]);
});

it('cannot delete collection from another user', function () {
    $otherUser = User::factory()->regularUser()->create();

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $otherUser->id,
    ]);

    $response = $this->deleteJson("/api/v1/collections/{$collection->id}");

    $response->assertForbidden();
});

it('can delete collection from another user as admin', function () {
    $admin = User::factory()->admin()->create();
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($admin)->deleteJson("/api/v1/collections/{$collection->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('blueprint_collections', [
        'id' => $collection->id,
    ]);
});

it('can delete collection from another user as moderator', function () {
    $moderator = User::factory()->moderator()->create();
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->actingAs($moderator)->deleteJson("/api/v1/collections/{$collection->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('blueprint_collections', [
        'id' => $collection->id,
    ]);
});

it('generates slug from title when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'My Awesome Collection',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'title' => 'My Awesome Collection',
                'slug' => 'my-awesome-collection',
            ],
        ]);
});

it('generates slug from title when updating a collection', function () {
    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Old Title',
        'slug' => 'old-title',
    ]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'title' => 'Updated Collection Title',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'title' => 'Updated Collection Title',
                'slug' => 'updated-collection-title',
            ],
        ]);
});

it('returns collection with blueprints when loaded', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Blueprint 1',
    ]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Blueprint 2',
    ]);

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $collection->blueprints()->sync([$blueprint1->id, $blueprint2->id]);

    $response = $this->getJson("/api/v1/collections/{$collection->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $collection->id,
                'blueprints' => [
                    [
                        'id' => $blueprint1->id,
                        'title' => 'Blueprint 1',
                    ],
                    [
                        'id' => $blueprint2->id,
                        'title' => 'Blueprint 2',
                    ],
                ],
            ],
        ]);
});

it('can create collection without blueprints', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Empty Collection',
    ]);

    $response->assertSuccessful();

    $collection = BlueprintCollection::where('title', 'Empty Collection')->first();
    expect($collection->blueprints)->toHaveCount(0);
});

it('can update collection to remove all blueprints', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $collection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $collection->blueprints()->sync([$blueprint1->id]);

    $response = $this->putJson("/api/v1/collections/{$collection->id}", [
        'blueprints' => [],
    ]);

    $response->assertSuccessful();

    $collection->refresh();
    expect($collection->blueprints)->toHaveCount(0);
});

it('requires authentication to create collections', function () {
    $response = $this->actingAsGuest()->postJson('/api/v1/collections', [
        'title' => 'Test Collection',
    ]);

    $response->assertUnauthorized();
});

it('returns 404 when showing non-existent collection', function () {
    // Note: This requires authentication, so we need to be authenticated
    $response = $this->getJson('/api/v1/collections/non-existent-id');

    // The route binding will fail before authorization check
    $response->assertNotFound();
});

it('defaults to draft status when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Default Status Collection',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'status' => Status::DRAFT->value,
            ],
        ]);

    $this->assertDatabaseHas('blueprint_collections', [
        'title' => 'Default Status Collection',
        'status' => Status::DRAFT->value,
    ]);
});

it('defaults to not anonymous when creating a collection', function () {
    $response = $this->postJson('/api/v1/collections', [
        'title' => 'Default Anonymous Collection',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'creator' => [
                    'id' => $this->user->id,
                ],
            ],
        ]);

    $this->assertDatabaseHas('blueprint_collections', [
        'title' => 'Default Anonymous Collection',
        'is_anonymous' => false,
    ]);
});
