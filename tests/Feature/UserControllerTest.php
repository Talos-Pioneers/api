<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('can upgrade user to moderator as admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->regularUser()->create();

    $response = $this->actingAs($admin)->postJson("/api/v1/users/{$user->id}/upgrade-to-moderator");

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'User upgraded to moderator successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
        ]);

    $user->refresh();
    expect($user->hasRole('Moderator'))->toBeTrue();
});

it('cannot upgrade user to moderator as regular user', function () {
    $regularUser = User::factory()->regularUser()->create();
    $user = User::factory()->regularUser()->create();

    $response = $this->actingAs($regularUser)->postJson("/api/v1/users/{$user->id}/upgrade-to-moderator");

    $response->assertForbidden();
});

it('cannot upgrade user to moderator as moderator', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->regularUser()->create();

    $response = $this->actingAs($moderator)->postJson("/api/v1/users/{$user->id}/upgrade-to-moderator");

    $response->assertForbidden();
});

it('requires authentication to upgrade user', function () {
    $user = User::factory()->regularUser()->create();

    $response = $this->postJson("/api/v1/users/{$user->id}/upgrade-to-moderator");

    $response->assertUnauthorized();
});
