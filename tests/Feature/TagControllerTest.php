<?php

use App\Enums\TagType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

it('can list all tags', function () {
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

    $response = $this->getJson('/api/tags');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'id' => $tag1->id,
            'name' => 'mining',
            'slug' => 'mining',
            'type' => TagType::BLUEPRINT_TAGS->value,
        ])
        ->assertJsonFragment([
            'id' => $tag2->id,
            'name' => 'refining',
            'slug' => 'refining',
            'type' => TagType::BLUEPRINT_TAGS->value,
        ]);
});

it('can create a tag', function () {
    $response = $this->postJson('/api/tags', [
        'name' => 'manufacturing',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'name' => 'manufacturing',
                'slug' => 'manufacturing',
                'type' => TagType::BLUEPRINT_TAGS->value,
            ],
        ]);

    $this->assertDatabaseHas('tags', [
        'type' => TagType::BLUEPRINT_TAGS->value,
    ]);

    $tag = Tag::where('type', TagType::BLUEPRINT_TAGS->value)
        ->where('name->en', 'manufacturing')
        ->first();

    expect($tag)->not->toBeNull();
    expect($tag->name)->toBe('manufacturing');
    expect($tag->slug)->toBe('manufacturing');
});

it('can create a tag with a specific type', function () {
    $response = $this->postJson('/api/tags', [
        'name' => 'custom-tag',
        'type' => TagType::BLUEPRINT_TAGS->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'name' => 'custom-tag',
                'slug' => 'custom-tag',
                'type' => TagType::BLUEPRINT_TAGS->value,
            ],
        ]);
});

it('validates name is required when creating a tag', function () {
    $response = $this->postJson('/api/tags', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates name is a string when creating a tag', function () {
    $response = $this->postJson('/api/tags', [
        'name' => 123,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates name max length when creating a tag', function () {
    $response = $this->postJson('/api/tags', [
        'name' => str_repeat('a', 256),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates type is a valid enum when creating a tag', function () {
    $response = $this->postJson('/api/tags', [
        'name' => 'test-tag',
        'type' => 'invalid-type',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('can show a single tag', function () {
    $tag = Tag::create([
        'name' => 'assembly',
        'slug' => 'assembly',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->getJson("/api/tags/{$tag->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $tag->id,
                'name' => 'assembly',
                'slug' => 'assembly',
                'type' => TagType::BLUEPRINT_TAGS->value,
            ],
        ]);
});

it('returns 404 when showing a non-existent tag', function () {
    $response = $this->getJson('/api/tags/999');

    $response->assertNotFound();
});

it('can update a tag name', function () {
    $tag = Tag::create([
        'name' => 'old-name',
        'slug' => 'old-name',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'name' => 'new-name',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $tag->id,
                'name' => 'new-name',
                'slug' => 'new-name',
                'type' => TagType::BLUEPRINT_TAGS->value,
            ],
        ]);

    $tag->refresh();

    expect($tag->name)->toBe('new-name');
    expect($tag->slug)->toBe('new-name');
});

it('can update a tag type', function () {
    $tag = Tag::create([
        'name' => 'test-tag',
        'slug' => 'test-tag',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'type' => TagType::BLUEPRINT_TAGS->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $tag->id,
                'name' => 'test-tag',
                'type' => TagType::BLUEPRINT_TAGS->value,
            ],
        ]);
});

it('can update both name and type', function () {
    $tag = Tag::create([
        'name' => 'old-name',
        'slug' => 'old-name',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'name' => 'updated-name',
        'type' => TagType::BLUEPRINT_TAGS->value,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $tag->id,
                'name' => 'updated-name',
                'slug' => 'updated-name',
                'type' => TagType::BLUEPRINT_TAGS->value,
            ],
        ]);
});

it('validates name is a string when updating a tag', function () {
    $tag = Tag::create([
        'name' => 'test-tag',
        'slug' => 'test-tag',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'name' => 123,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates name max length when updating a tag', function () {
    $tag = Tag::create([
        'name' => 'test-tag',
        'slug' => 'test-tag',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'name' => str_repeat('a', 256),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates type is a valid enum when updating a tag', function () {
    $tag = Tag::create([
        'name' => 'test-tag',
        'slug' => 'test-tag',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'type' => 'invalid-type',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('returns 404 when updating a non-existent tag', function () {
    $response = $this->putJson('/api/tags/999', [
        'name' => 'updated-name',
    ]);

    $response->assertNotFound();
});

it('can delete a tag', function () {
    $tag = Tag::create([
        'name' => 'to-delete',
        'slug' => 'to-delete',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->deleteJson("/api/tags/{$tag->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('tags', [
        'id' => $tag->id,
    ]);
});

it('returns 404 when deleting a non-existent tag', function () {
    $response = $this->deleteJson('/api/tags/999');

    $response->assertNotFound();
});

it('generates slug from name when creating a tag', function () {
    $response = $this->postJson('/api/tags', [
        'name' => 'Test Tag Name',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'name' => 'Test Tag Name',
                'slug' => 'test-tag-name',
            ],
        ]);
});

it('generates slug from name when updating a tag', function () {
    $tag = Tag::create([
        'name' => 'old-name',
        'slug' => 'old-name',
        'type' => TagType::BLUEPRINT_TAGS,
    ]);

    $response = $this->putJson("/api/tags/{$tag->id}", [
        'name' => 'Updated Tag Name',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'name' => 'Updated Tag Name',
                'slug' => 'updated-tag-name',
            ],
        ]);
});
