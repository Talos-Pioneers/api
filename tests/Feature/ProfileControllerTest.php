<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can view own profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertSuccessful()
        ->assertJson([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ]);
});

it('requires authentication to view profile', function () {
    $response = $this->getJson('/api/profile');

    $response->assertUnauthorized();
});

it('can update own username', function () {
    $user = User::factory()->create(['username' => 'oldusername']);

    $response = $this->actingAs($user)->putJson('/api/profile', [
        'username' => 'newusername',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'username' => 'newusername',
                'email' => $user->email,
            ],
        ]);

    $user->refresh();
    expect($user->username)->toBe('newusername');
});

it('requires authentication to update profile', function () {
    $response = $this->putJson('/api/profile', [
        'username' => 'newusername',
    ]);

    $response->assertUnauthorized();
});

it('requires username to update profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/profile', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('prevents duplicate username when updating profile', function () {
    $user1 = User::factory()->create(['username' => 'user1']);
    $user2 = User::factory()->create(['username' => 'user2']);

    $response = $this->actingAs($user1)->putJson('/api/profile', [
        'username' => 'user2',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('allows user to keep same username when updating profile', function () {
    $user = User::factory()->create(['username' => 'myusername']);

    $response = $this->actingAs($user)->putJson('/api/profile', [
        'username' => 'myusername',
    ]);

    $response->assertSuccessful();
});

it('validates username max length', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/profile', [
        'username' => str_repeat('a', 256),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});
