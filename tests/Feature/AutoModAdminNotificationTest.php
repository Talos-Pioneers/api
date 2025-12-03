<?php

use App\Enums\GameVersion;
use App\Enums\ServerRegion;
use App\Enums\Status;
use App\Mail\AutoModFlaggedMail;
use App\Models\Blueprint;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use OpenAI\Client;
use OpenAI\Responses\Moderations\CreateResponse;
use OpenAI\Testing\ClientFake;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
    $this->admin1 = User::factory()->admin()->create();
    $this->admin2 = User::factory()->admin()->create();
    Config::set('services.auto_mod.enabled', false);
    Config::set('services.openai.api_key', 'test-key');
});

it('sends email to all admins when blueprint creation fails moderation', function () {
    Mail::fake();
    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Inappropriate Title',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(Status::REVIEW->value);

    Mail::assertQueued(AutoModFlaggedMail::class, 2);

    Mail::assertQueued(AutoModFlaggedMail::class, function (AutoModFlaggedMail $mail) {
        return $mail->hasTo($this->admin1->email)
            && $mail->contentType === 'blueprint'
            && $mail->contentTitle === 'Inappropriate Title'
            && $mail->author->id === $this->user->id;
    });

    Mail::assertQueued(AutoModFlaggedMail::class, function (AutoModFlaggedMail $mail) {
        return $mail->hasTo($this->admin2->email);
    });
});

it('sends email to all admins when blueprint update fails moderation', function () {
    Mail::fake();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Original Title',
    ]);

    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['harassment' => true],
                    'category_scores' => ['harassment' => 0.9],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'title' => 'Updated Inappropriate Title',
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();
    expect($response->json('data.status'))->toBe(Status::REVIEW->value);

    Mail::assertQueued(AutoModFlaggedMail::class, 2);

    Mail::assertQueued(AutoModFlaggedMail::class, function (AutoModFlaggedMail $mail) {
        return $mail->hasTo($this->admin1->email)
            && $mail->contentType === 'blueprint'
            && $mail->contentTitle === 'Updated Inappropriate Title';
    });
});

it('does not send email to admins when blueprint creation passes moderation', function () {
    Mail::fake();
    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Safe Title',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();

    Mail::assertNotQueued(AutoModFlaggedMail::class);
});

it('does not send email to admins when automod is disabled', function () {
    Mail::fake();

    $this->actingAs($this->user);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Any Title',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();

    Mail::assertNotQueued(AutoModFlaggedMail::class);
});

it('sends email to all admins when comment fails moderation', function () {
    Mail::fake();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
        'title' => 'Test Blueprint',
    ]);

    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['violence' => true],
                    'category_scores' => ['violence' => 0.85],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    RateLimiter::clear("comment:user:{$this->user->id}");

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/comments", [
        'comment' => 'Inappropriate comment content',
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('comments', [
        'commentable_id' => $blueprint->id,
        'comment' => 'Inappropriate comment content',
        'is_approved' => false,
    ]);

    Mail::assertQueued(AutoModFlaggedMail::class, 2);

    Mail::assertQueued(AutoModFlaggedMail::class, function (AutoModFlaggedMail $mail) use ($blueprint) {
        return $mail->hasTo($this->admin1->email)
            && $mail->contentType === 'comment'
            && $mail->contentTitle === $blueprint->title
            && $mail->author->id === $this->user->id;
    });
});

it('does not send email to admins when comment passes moderation', function () {
    Mail::fake();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);

    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    RateLimiter::clear("comment:user:{$this->user->id}");

    $response = $this->postJson("/api/v1/blueprints/{$blueprint->id}/comments", [
        'comment' => 'Safe comment content',
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('comments', [
        'commentable_id' => $blueprint->id,
        'comment' => 'Safe comment content',
        'is_approved' => true,
    ]);

    Mail::assertNotQueued(AutoModFlaggedMail::class);
});

it('includes flagged content details in email', function () {
    Mail::fake();
    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true, 'violence' => true],
                    'category_scores' => ['hate' => 0.95, 'violence' => 0.75],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->postJson('/api/v1/blueprints', [
        'code' => 'EFE750a2A78o53Ela',
        'title' => 'Flagged Content Title',
        'description' => 'Flagged description',
        'version' => GameVersion::CBT_3->value,
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();

    Mail::assertQueued(AutoModFlaggedMail::class, function (AutoModFlaggedMail $mail) {
        return count($mail->flaggedTexts) > 0
            && isset($mail->flaggedTexts[0]['categories']);
    });
});

it('uses existing blueprint title when updating without new title', function () {
    Mail::fake();

    $blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'title' => 'Existing Blueprint Title',
    ]);

    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->putJson("/api/v1/blueprints/{$blueprint->id}", [
        'description' => 'Inappropriate description',
        'server_region' => ServerRegion::AMERICA_EUROPE->value,
    ]);

    $response->assertSuccessful();

    Mail::assertQueued(AutoModFlaggedMail::class, function (AutoModFlaggedMail $mail) {
        return $mail->contentTitle === 'Existing Blueprint Title';
    });
});
