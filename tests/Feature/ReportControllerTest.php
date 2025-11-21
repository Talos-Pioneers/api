<?php

use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
    $this->actingAs($this->user);
});

it('can create a report for a blueprint', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'This blueprint contains inappropriate content',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Report submitted successfully.',
            'data' => [
                'reportable_type' => Blueprint::class,
                'reportable_id' => $blueprint->id,
                'reason' => 'This blueprint contains inappropriate content',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'reportable_type',
                'reportable_id',
                'reason',
                'created_at',
            ],
        ]);

    $this->assertDatabaseHas('reports', [
        'user_id' => $this->user->id,
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'This blueprint contains inappropriate content',
    ]);
});

it('can create a report for a collection', function () {
    $collection = BlueprintCollection::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => BlueprintCollection::class,
        'reportable_id' => $collection->id,
        'reason' => 'This collection violates community guidelines',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Report submitted successfully.',
            'data' => [
                'reportable_type' => BlueprintCollection::class,
                'reportable_id' => $collection->id,
                'reason' => 'This collection violates community guidelines',
            ],
        ]);

    $this->assertDatabaseHas('reports', [
        'user_id' => $this->user->id,
        'reportable_type' => BlueprintCollection::class,
        'reportable_id' => $collection->id,
        'reason' => 'This collection violates community guidelines',
    ]);
});

it('requires authentication to create a report', function () {
    $blueprint = Blueprint::factory()->create();

    $this->actingAsGuest();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
    ]);

    $response->assertUnauthorized();
});

it('prevents duplicate reports from the same user for the same item', function () {
    $blueprint = Blueprint::factory()->create();

    // Create first report
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'First report',
    ]);

    $response->assertSuccessful();

    // Try to create duplicate report
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'Second report attempt',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'You have already reported this item.',
        ]);

    // Should only have one report in database
    $this->assertDatabaseCount('reports', 1);
});

it('validates reportable_type is required', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_id' => $blueprint->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reportable_type']);
});

it('validates reportable_type is a valid model class', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => 'InvalidModel',
        'reportable_id' => $blueprint->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reportable_type']);
});

it('validates reportable_id is required', function () {
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reportable_id']);
});

it('validates reportable_id exists for blueprint', function () {
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => 'non-existent-id',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reportable_id']);
});

it('validates reportable_id exists for collection', function () {
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => BlueprintCollection::class,
        'reportable_id' => 'non-existent-id',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reportable_id']);
});

it('validates reason field is nullable', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('reports', [
        'user_id' => $this->user->id,
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => null,
    ]);
});

it('validates reason field max length', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => str_repeat('a', 1001),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

it('validates reason is a string', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 12345,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

it('can report different items', function () {
    $blueprint1 = Blueprint::factory()->create();
    $blueprint2 = Blueprint::factory()->create();

    // Report first blueprint
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint1->id,
        'reason' => 'Report 1',
    ]);

    $response->assertSuccessful();

    // Report second blueprint (should be allowed)
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint2->id,
        'reason' => 'Report 2',
    ]);

    $response->assertSuccessful();

    // Should have two reports
    $this->assertDatabaseCount('reports', 2);
});

it('can report same item type but different instances', function () {
    $blueprint = Blueprint::factory()->create();
    $collection = BlueprintCollection::factory()->create();

    // Report blueprint
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'Blueprint report',
    ]);

    $response->assertSuccessful();

    // Report collection (should be allowed even if same user)
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => BlueprintCollection::class,
        'reportable_id' => $collection->id,
        'reason' => 'Collection report',
    ]);

    $response->assertSuccessful();

    // Should have two reports
    $this->assertDatabaseCount('reports', 2);
});

it('allows different users to report the same item', function () {
    $blueprint = Blueprint::factory()->create();
    $otherUser = User::factory()->create();

    // First user reports
    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'Report from user 1',
    ]);

    $response->assertSuccessful();

    // Second user reports same item (should be allowed)
    $response = $this->actingAs($otherUser)->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'Report from user 2',
    ]);

    $response->assertSuccessful();

    // Should have two reports
    $this->assertDatabaseCount('reports', 2);
});

it('returns 201 status code when creating a report', function () {
    $blueprint = Blueprint::factory()->create();

    $response = $this->postJson('/api/v1/reports', [
        'reportable_type' => Blueprint::class,
        'reportable_id' => $blueprint->id,
        'reason' => 'Test report',
    ]);

    $response->assertStatus(201);
});
