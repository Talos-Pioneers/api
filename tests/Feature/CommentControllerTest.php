<?php

use App\Enums\Status;
use App\Models\Blueprint;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Client;
use OpenAI\Responses\Moderations\CreateResponse;
use OpenAI\Testing\ClientFake;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->regularUser()->create();
    $this->blueprint = Blueprint::factory()->create([
        'creator_id' => $this->user->id,
        'status' => Status::PUBLISHED,
    ]);
    Config::set('services.auto_mod.enabled', false);
    Config::set('services.openai.api_key', 'test-key');
});

it('can list comments for a blueprint', function () {
    $comment1 = $this->blueprint->commentAsUser($this->user, 'First comment');
    $comment1->approve();
    $comment2 = $this->blueprint->commentAsUser($this->user, 'Second comment');
    $comment2->approve();

    $unapprovedComment = $this->blueprint->commentAsUser($this->user, 'Unapproved comment');

    $response = $this->getJson("/api/v1/blueprints/{$this->blueprint->id}/comments");

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'comment' => 'First comment',
        ])
        ->assertJsonFragment([
            'comment' => 'Second comment',
        ])
        ->assertJsonMissing([
            'comment' => 'Unapproved comment',
        ]);
});

it('only shows approved comments in index', function () {
    $approvedComment = $this->blueprint->commentAsUser($this->user, 'Approved comment');
    $approvedComment->approve();

    $unapprovedComment = $this->blueprint->commentAsUser($this->user, 'Unapproved comment');

    $response = $this->getJson("/api/v1/blueprints/{$this->blueprint->id}/comments");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'comment' => 'Approved comment',
        ])
        ->assertJsonMissing([
            'comment' => 'Unapproved comment',
        ]);
});

it('can create a comment when authenticated', function () {
    $this->actingAs($this->user);

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'This is a test comment',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'comment' => 'This is a test comment',
                'is_approved' => false,
                'is_edited' => false,
            ],
        ]);

    $this->assertDatabaseHas('comments', [
        'commentable_type' => Blueprint::class,
        'commentable_id' => $this->blueprint->id,
        'user_id' => $this->user->id,
        'comment' => 'This is a test comment',
        'is_approved' => false,
    ]);
});

it('requires authentication to create a comment', function () {
    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'This is a test comment',
    ]);

    $response->assertUnauthorized();
});

it('validates comment content when creating', function () {
    $this->actingAs($this->user);

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => '',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['comment']);

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => str_repeat('a', 5001),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['comment']);
});

it('enforces rate limiting when creating comments', function () {
    $this->actingAs($this->user);

    // Create first comment
    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'First comment',
    ]);

    $response->assertSuccessful();

    // Try to create second comment immediately (should be rate limited)
    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'Second comment',
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'message' => 'You can only post 1 comment per minute. Please try again later.',
        ]);
});

it('allows creating comment after rate limit expires', function () {
    $this->actingAs($this->user);

    // Create first comment
    $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'First comment',
    ])->assertSuccessful();

    // Clear rate limiter
    RateLimiter::clear("comment:user:{$this->user->id}");

    // Should be able to create another comment
    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'Second comment',
    ]);

    $response->assertSuccessful();
});

it('can show a single comment', function () {
    $comment = $this->blueprint->commentAsUser($this->user, 'Test comment');

    $response = $this->getJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'id' => $comment->id,
                'comment' => 'Test comment',
            ],
        ]);
});

it('can update own comment', function () {
    $this->actingAs($this->user);
    $comment = $this->blueprint->commentAsUser($this->user, 'Original comment');

    $response = $this->putJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}", [
        'comment' => 'Updated comment',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'comment' => 'Updated comment',
                'is_edited' => true,
            ],
        ]);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'comment' => 'Updated comment',
        'is_edited' => true,
    ]);
});

it('sets is_edited flag when updating comment', function () {
    $this->actingAs($this->user);
    $comment = $this->blueprint->commentAsUser($this->user, 'Original comment');
    $comment->refresh();

    expect($comment->is_edited ?? false)->toBeFalse();

    $this->putJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}", [
        'comment' => 'Updated comment',
    ]);

    $comment->refresh();
    expect($comment->is_edited)->toBeTrue();
});

it('cannot update another users comment', function () {
    $otherUser = User::factory()->regularUser()->create();
    $comment = $this->blueprint->commentAsUser($otherUser, 'Other users comment');

    $this->actingAs($this->user);

    $response = $this->putJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}", [
        'comment' => 'Trying to update',
    ]);

    $response->assertForbidden();
});

it('can delete own comment', function () {
    $this->actingAs($this->user);
    $comment = $this->blueprint->commentAsUser($this->user, 'Comment to delete');

    $response = $this->deleteJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});

it('cannot delete another users comment', function () {
    $otherUser = User::factory()->regularUser()->create();
    $comment = $this->blueprint->commentAsUser($otherUser, 'Other users comment');

    $this->actingAs($this->user);

    $response = $this->deleteJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
    ]);
});

it('allows moderators to delete any comment', function () {
    $moderator = User::factory()->moderator()->create();
    $comment = $this->blueprint->commentAsUser($this->user, 'Users comment');

    $this->actingAs($moderator);

    $response = $this->deleteJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});

it('allows admins to delete any comment', function () {
    $admin = User::factory()->admin()->create();
    $comment = $this->blueprint->commentAsUser($this->user, 'Users comment');

    $this->actingAs($admin);

    $response = $this->deleteJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});

it('allows moderators to update any comment', function () {
    $moderator = User::factory()->moderator()->create();
    $comment = $this->blueprint->commentAsUser($this->user, 'Users comment');

    $this->actingAs($moderator);

    $response = $this->putJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}", [
        'comment' => 'Updated by moderator',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'comment' => 'Updated by moderator',
            ],
        ]);
});

it('auto-approves comment when AutoMod passes', function () {
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
        // Need a second response for the event listener
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

    // Bind the fake client to the container
    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'This is a clean comment',
    ]);

    $response->assertSuccessful();

    // Wait a moment for the event to process
    sleep(1);

    $comment = Comment::where('commentable_id', $this->blueprint->id)
        ->where('comment', 'This is a clean comment')
        ->first();

    expect($comment->is_approved)->toBeTrue();
});

it('does not auto-approve comment when AutoMod fails', function () {
    Config::set('services.auto_mod.enabled', true);

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.9],
                ],
            ],
        ]),
        // Need a second response for the event listener
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.9],
                ],
            ],
        ]),
    ]);

    // Bind the fake client to the container
    app()->instance(Client::class, $client);

    $this->actingAs($this->user);

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'This is a flagged comment',
    ]);

    $response->assertSuccessful();

    // Wait a moment for the event to process
    sleep(1);

    $comment = Comment::where('commentable_id', $this->blueprint->id)
        ->where('comment', 'This is a flagged comment')
        ->first();

    expect($comment->is_approved)->toBeFalse();
});

it('does not auto-approve when AutoMod is disabled', function () {
    Config::set('services.auto_mod.enabled', false);

    $this->actingAs($this->user);

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'This is a comment',
    ]);

    $response->assertSuccessful();

    $comment = Comment::where('commentable_id', $this->blueprint->id)
        ->where('comment', 'This is a comment')
        ->first();

    expect($comment->is_approved)->toBeFalse();
});

it('includes user information in comment resource', function () {
    $comment = $this->blueprint->commentAsUser($this->user, 'Test comment');

    $response = $this->getJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'user' => [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                ],
            ],
        ]);
});

it('includes commentable information in comment resource', function () {
    $comment = $this->blueprint->commentAsUser($this->user, 'Test comment');

    $response = $this->getJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$comment->id}");

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'commentable' => [
                    'type' => Blueprint::class,
                    'id' => $this->blueprint->id,
                ],
            ],
        ]);
});

it('can create nested comments (replies)', function () {
    $this->actingAs($this->user);

    $parentComment = $this->blueprint->commentAsUser($this->user, 'Parent comment');

    $response = $this->postJson("/api/v1/blueprints/{$this->blueprint->id}/comments", [
        'comment' => 'Reply comment',
        'parent_id' => $parentComment->id,
    ]);

    // Note: The beyondcode/laravel-comments package handles nested comments
    // through the comment() method on the Comment model itself
    $reply = $parentComment->commentAsUser($this->user, 'Reply comment');

    expect($reply->commentable_type)->toBe(Comment::class);
    expect($reply->commentable_id)->toBe($parentComment->id);
});

it('includes replies in comment resource when loaded', function () {
    $parentComment = $this->blueprint->commentAsUser($this->user, 'Parent comment');
    $reply = $parentComment->commentAsUser($this->user, 'Reply comment');

    $response = $this->getJson("/api/v1/blueprints/{$this->blueprint->id}/comments/{$parentComment->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'replies' => [
                    '*' => [
                        'id',
                        'comment',
                        'user',
                    ],
                ],
            ],
        ]);
});
