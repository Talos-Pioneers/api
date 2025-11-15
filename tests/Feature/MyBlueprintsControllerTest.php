<?php

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\Status;
use App\Enums\TagType;
use App\Models\Blueprint;
use App\Models\Facility;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
    $this->actingAs($this->user);
});

it('requires authentication', function () {
    $this->actingAsGuest();

    $response = $this->getJson('/api/v1/my/blueprints');

    $response->assertUnauthorized();
});

it('returns only the authenticated user\'s blueprints', function () {
    $otherUser = User::factory()->create();

    $myBlueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'My Blueprint 1',
    ]);

    $myBlueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
        'title' => 'My Blueprint 2',
    ]);

    $otherBlueprint = Blueprint::factory()->create([
        'creator_id' => $otherUser->id,
        'status' => Status::PUBLISHED,
        'title' => 'Other User Blueprint',
    ]);

    $response = $this->getJson('/api/v1/my/blueprints');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $myBlueprint1->id,
            'title' => 'My Blueprint 1',
        ])
        ->assertJsonFragment([
            'id' => $myBlueprint2->id,
            'title' => 'My Blueprint 2',
        ])
        ->assertJsonMissing([
            'id' => $otherBlueprint->id,
        ]);
});

it('includes all statuses for user\'s blueprints', function () {
    $published = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Published Blueprint',
    ]);

    $draft = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
        'title' => 'Draft Blueprint',
    ]);

    $response = $this->getJson('/api/v1/my/blueprints');

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

it('includes anonymous blueprints created by the user', function () {
    $anonymousBlueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'is_anonymous' => true,
        'title' => 'Anonymous Blueprint',
    ]);

    $response = $this->getJson('/api/v1/my/blueprints');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $anonymousBlueprint->id,
            'title' => 'Anonymous Blueprint',
            'creator' => null,
        ]);
});

it('can filter by status', function () {
    Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    $draft = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::DRAFT,
    ]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[status]='.Status::DRAFT->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $draft->id,
            'status' => Status::DRAFT->value,
        ]);
});

it('can filter by region', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'region' => Region::VALLEY_IV,
    ]);

    Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'region' => Region::JINLONG,
    ]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[region]='.Region::VALLEY_IV->value);

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
            'region' => Region::VALLEY_IV->value,
        ]);
});

it('can filter by version', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'version' => GameVersion::CBT_3,
    ]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'version' => GameVersion::CBT_3,
    ]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[version]='.GameVersion::CBT_3->value);

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
            'version' => GameVersion::CBT_3->value,
        ])
        ->assertJsonFragment([
            'id' => $blueprint2->id,
            'version' => GameVersion::CBT_3->value,
        ]);
});

it('can filter by is_anonymous', function () {
    $anonymous = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'is_anonymous' => true,
    ]);

    Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'is_anonymous' => false,
    ]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[is_anonymous]=1');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $anonymous->id,
            'creator' => null,
        ]);
});

it('can filter by facility slug', function () {
    $facility1 = Facility::factory()->create(['slug' => 'mining-facility']);
    $facility2 = Facility::factory()->create(['slug' => 'refining-facility']);

    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint1->facilities()->attach($facility1->id, ['quantity' => 2]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint2->facilities()->attach($facility2->id, ['quantity' => 1]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[facility]=mining-facility');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('can filter by item input slug', function () {
    $item1 = Item::factory()->create(['slug' => 'iron-ore']);
    $item2 = Item::factory()->create(['slug' => 'copper-ore']);

    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint1->itemInputs()->attach($item1->id, ['quantity' => 10]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint2->itemInputs()->attach($item2->id, ['quantity' => 5]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[item_input]=iron-ore');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('can filter by item output slug', function () {
    $item1 = Item::factory()->create(['slug' => 'iron-plate']);
    $item2 = Item::factory()->create(['slug' => 'copper-plate']);

    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint1->itemOutputs()->attach($item1->id, ['quantity' => 8]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint2->itemOutputs()->attach($item2->id, ['quantity' => 4]);

    $response = $this->getJson('/api/v1/my/blueprints?filter[item_output]=iron-plate');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('can filter by tags', function () {
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
        'creator_id' => $this->user->id,
    ]);
    $blueprint1->syncTags([$tag1]);

    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);
    $blueprint2->syncTags([$tag2]);

    $response = $this->getJson("/api/v1/my/blueprints?filter[tags.id]={$tag1->id}");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $blueprint1->id,
        ])
        ->assertJsonMissing([
            'id' => $blueprint2->id,
        ]);
});

it('can sort by title', function () {
    Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Z Blueprint',
    ]);
    Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'A Blueprint',
    ]);
    Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'M Blueprint',
    ]);

    $response = $this->getJson('/api/v1/my/blueprints?sort=title');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['title'])->toBe('A Blueprint');
    expect($data[1]['title'])->toBe('M Blueprint');
    expect($data[2]['title'])->toBe('Z Blueprint');
});

it('can sort by created_at', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);
    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(1),
    ]);
    $blueprint3 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/my/blueprints?sort=created_at');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($blueprint1->id);
    expect($data[1]['id'])->toBe($blueprint2->id);
    expect($data[2]['id'])->toBe($blueprint3->id);
});

it('defaults to sorting by created_at', function () {
    $blueprint1 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);
    $blueprint2 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now()->subDays(1),
    ]);
    $blueprint3 = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/my/blueprints');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data[0]['id'])->toBe($blueprint1->id);
    expect($data[1]['id'])->toBe($blueprint2->id);
    expect($data[2]['id'])->toBe($blueprint3->id);
});

it('paginates results', function () {
    Blueprint::factory()->count(30)->create([
        'creator_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/v1/my/blueprints');

    $response->assertSuccessful()
        ->assertJsonCount(25, 'data')
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

it('includes likes_count and copies_count', function () {
    $otherUser = User::factory()->create();
    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
    ]);

    $blueprint->likes()->attach($otherUser->id);
    $blueprint->copies()->create([
        'user_id' => $otherUser->id,
        'ip_address' => '127.0.0.1',
        'copied_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/my/blueprints');

    $response->assertSuccessful()
        ->assertJsonFragment([
            'id' => $blueprint->id,
            'likes_count' => 1,
            'copies_count' => 1,
        ]);
});
