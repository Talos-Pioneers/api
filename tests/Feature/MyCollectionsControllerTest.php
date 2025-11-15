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

it('requires authentication', function () {
    $this->actingAsGuest();

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertUnauthorized();
});

it('returns only the authenticated user\'s collections', function () {
    $otherUser = User::factory()->create();

    $myCollection1 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'My Collection 1',
    ]);

    $myCollection2 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
        'title' => 'My Collection 2',
    ]);

    $otherCollection = BlueprintCollection::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
        'title' => 'Other User Collection',
    ]);

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $myCollection1->id,
            'title' => 'My Collection 1',
        ])
        ->assertJsonFragment([
            'id' => $myCollection2->id,
            'title' => 'My Collection 2',
        ])
        ->assertJsonMissing([
            'id' => $otherCollection->id,
        ]);
});

it('includes all statuses for user\'s collections', function () {
    $published = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Published Collection',
    ]);

    $draft = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
        'title' => 'Draft Collection',
    ]);

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $published->id,
            'status' => Status::PUBLISHED->value,
        ])
        ->assertJsonFragment([
            'id' => $draft->id,
            'status' => Status::DRAFT->value,
        ]);
});

it('includes anonymous collections created by the user', function () {
    $anonymousCollection = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'is_anonymous' => true,
        'title' => 'Anonymous Collection',
    ]);

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $anonymousCollection->id,
            'title' => 'Anonymous Collection',
            'creator' => null,
        ]);
});

it('can filter by status', function () {
    BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $draft = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->getJson('/api/v1/my/collections?filter[status]='.Status::DRAFT->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $draft->id,
            'status' => Status::DRAFT->value,
        ]);
});

it('can filter by is_anonymous', function () {
    $anonymous = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'is_anonymous' => true,
    ]);

    BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'is_anonymous' => false,
    ]);

    $response = $this->getJson('/api/v1/my/collections?filter[is_anonymous]=1');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $anonymous->id,
            'creator' => null,
        ]);
});

it('can sort by title', function () {
    BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Z Collection',
    ]);
    BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'A Collection',
    ]);
    BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'M Collection',
    ]);

    $response = $this->getJson('/api/v1/my/collections?sort=title');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['title'])->toBe('A Collection');
    expect($data[1]['title'])->toBe('M Collection');
    expect($data[2]['title'])->toBe('Z Collection');
});

it('can sort by created_at', function () {
    $collection1 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);
    $collection2 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(1),
    ]);
    $collection3 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/my/collections?sort=created_at');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($collection1->id);
    expect($data[1]['id'])->toBe($collection2->id);
    expect($data[2]['id'])->toBe($collection3->id);
});

it('defaults to sorting by created_at', function () {
    $collection1 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);
    $collection2 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(1),
    ]);
    $collection3 = BlueprintCollection::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($collection1->id);
    expect($data[1]['id'])->toBe($collection2->id);
    expect($data[2]['id'])->toBe($collection3->id);
});

it('paginates results', function () {
    BlueprintCollection::factory()->count(30)->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertSuccessful()
        ->assertJsonCount(25, 'data')
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('includes blueprints when loaded', function () {
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
    ]);

    $collection->blueprints()->sync([$blueprint1->id, $blueprint2->id]);

    $response = $this->getJson('/api/v1/my/collections');

    $response->assertSuccessful();
    $data = $response->json('data');
    $foundCollection = collect($data)->firstWhere('id', $collection->id);

    expect($foundCollection)->not->toBeNull();
    expect($foundCollection['blueprints'])->toHaveCount(2);
    expect($foundCollection['blueprints'][0])->toHaveKeys(['id', 'title', 'slug', 'code']);
    expect(collect($foundCollection['blueprints'])->pluck('id')->toArray())->toContain($blueprint1->id, $blueprint2->id);
});
