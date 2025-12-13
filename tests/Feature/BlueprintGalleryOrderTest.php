<?php

use App\Enums\ServerRegion;
use App\Models\Blueprint;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
    Config::set('services.auto_mod.enabled', false);
});

it('updates order of existing gallery images', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['creator_id' => $user->id]);

    // Upload 3 initial images
    $image1 = UploadedFile::fake()->image('image1.jpg');
    $image2 = UploadedFile::fake()->image('image2.jpg');
    $image3 = UploadedFile::fake()->image('image3.jpg');

    $blueprint->addMedia($image1)->toMediaCollection('gallery')->setCustomProperty('order', 0);
    $blueprint->addMedia($image2)->toMediaCollection('gallery')->setCustomProperty('order', 1);
    $blueprint->addMedia($image3)->toMediaCollection('gallery')->setCustomProperty('order', 2);

    $media = $blueprint->getMedia('gallery');
    $mediaIds = $media->pluck('id')->all();

    // Reorder: 3, 1, 2
    $response = actingAs($user)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'code' => $blueprint->code,
        'title' => $blueprint->title,
        'version' => $blueprint->version->value,
        'status' => $blueprint->status->value,
        'server_region' => $blueprint->server_region?->value ?? ServerRegion::AMERICA_EUROPE->value,
        'gallery_keep_ids' => $mediaIds,
        'gallery_order' => [
            (string) $mediaIds[2], // image3 first
            (string) $mediaIds[0], // image1 second
            (string) $mediaIds[1], // image2 third
        ],
    ]);

    $response->assertSuccessful();

    $blueprint->refresh();
    $orderedMedia = $blueprint->getMedia('gallery')->sortBy(fn ($media) => $media->getCustomProperty('order') ?? PHP_INT_MAX);

    expect($orderedMedia->pluck('id')->values()->all())->toBe([
        $mediaIds[2],
        $mediaIds[0],
        $mediaIds[1],
    ]);
});

it('adds new images with correct order', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['creator_id' => $user->id]);

    $newImage1 = UploadedFile::fake()->image('new1.jpg');
    $newImage2 = UploadedFile::fake()->image('new2.jpg');

    $response = actingAs($user)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'code' => $blueprint->code,
        'title' => $blueprint->title,
        'version' => $blueprint->version->value,
        'status' => $blueprint->status->value,
        'server_region' => $blueprint->server_region?->value ?? ServerRegion::AMERICA_EUROPE->value,
        'gallery' => [$newImage1, $newImage2],
        'gallery_order' => ['new_0', 'new_1'],
    ]);

    $response->assertSuccessful();

    $blueprint->refresh();
    $media = $blueprint->getMedia('gallery');

    expect($media)->toHaveCount(2);
    expect($media[0]->order_column)->toBe(0);
    expect($media[1]->order_column)->toBe(1);
});

it('handles mixed order of existing and new images', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['creator_id' => $user->id]);

    // Add 2 existing images
    $image1 = UploadedFile::fake()->image('image1.jpg');
    $image2 = UploadedFile::fake()->image('image2.jpg');

    $media1 = $blueprint->addMedia($image1)->toMediaCollection('gallery');
    $media1->order_column = 0;
    $media1->save();

    $media2 = $blueprint->addMedia($image2)->toMediaCollection('gallery');
    $media2->order_column = 1;
    $media2->save();

    $media = $blueprint->getMedia('gallery');
    $mediaIds = $media->pluck('id')->all();

    // Add new images mixed with existing
    $newImage1 = UploadedFile::fake()->image('new1.jpg');
    $newImage2 = UploadedFile::fake()->image('new2.jpg');

    // Desired order: existing[0], new_0, existing[1], new_1
    $response = actingAs($user)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'code' => $blueprint->code,
        'title' => $blueprint->title,
        'version' => $blueprint->version->value,
        'status' => $blueprint->status->value,
        'server_region' => $blueprint->server_region?->value ?? ServerRegion::AMERICA_EUROPE->value,
        'gallery' => [$newImage1, $newImage2],
        'gallery_keep_ids' => $mediaIds,
        'gallery_order' => [
            (string) $mediaIds[0], // existing image 1
            'new_0',               // new image 1
            (string) $mediaIds[1], // existing image 2
            'new_1',               // new image 2
        ],
    ]);

    $response->assertSuccessful();

    $blueprint->refresh();
    $orderedMedia = $blueprint->getMedia('gallery')->sortBy(fn ($media) => $media->getCustomProperty('order') ?? PHP_INT_MAX)->values();

    expect($orderedMedia)->toHaveCount(4);
    expect($orderedMedia[0]->id)->toBe($mediaIds[0]);
    expect($orderedMedia[1]->order_column)->toBe(1);
    expect($orderedMedia[2]->id)->toBe($mediaIds[1]);
    expect($orderedMedia[3]->order_column)->toBe(3);
});

it('deletes images not in keep list while maintaining order', function () {
    $user = User::factory()->create();
    $blueprint = Blueprint::factory()->create(['creator_id' => $user->id]);

    // Upload 3 initial images
    $image1 = UploadedFile::fake()->image('image1.jpg');
    $image2 = UploadedFile::fake()->image('image2.jpg');
    $image3 = UploadedFile::fake()->image('image3.jpg');

    $blueprint->addMedia($image1)->toMediaCollection('gallery')->setCustomProperty('order', 0);
    $blueprint->addMedia($image2)->toMediaCollection('gallery')->setCustomProperty('order', 1);
    $blueprint->addMedia($image3)->toMediaCollection('gallery')->setCustomProperty('order', 2);

    $media = $blueprint->getMedia('gallery');
    $mediaIds = $media->pluck('id')->all();

    // Keep only image 1 and 3, delete image 2
    $response = actingAs($user)->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'code' => $blueprint->code,
        'title' => $blueprint->title,
        'version' => $blueprint->version->value,
        'status' => $blueprint->status->value,
        'server_region' => $blueprint->server_region?->value ?? ServerRegion::AMERICA_EUROPE->value,
        'gallery_keep_ids' => [$mediaIds[0], $mediaIds[2]],
        'gallery_order' => [
            (string) $mediaIds[0],
            (string) $mediaIds[2],
        ],
    ]);

    $response->assertSuccessful();

    $blueprint->refresh();
    $orderedMedia = $blueprint->getMedia('gallery');

    expect($orderedMedia)->toHaveCount(2);
    expect($orderedMedia->pluck('id')->values()->all())->not->toContain($mediaIds[1]);
});
