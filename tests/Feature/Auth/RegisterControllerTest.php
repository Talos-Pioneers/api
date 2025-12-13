<?php

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

it('can register a new user', function () {
    $response = $this->postJson('/register', [
        'email' => 'test@gmail.com',
        'username' => 'testuser',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Registration successful. Please check your email for the magic link.',
        ]);

    expect(User::where('email', 'test@gmail.com')->exists())->toBeTrue();
    Mail::assertQueued(\App\Mail\MagicLinkMail::class, function ($mail) {
        return $mail->type === 'register';
    });
});

it('requires email for registration', function () {
    $response = $this->postJson('/register', [
        'username' => 'testuser',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires username for registration', function () {
    $response = $this->postJson('/register', [
        'email' => 'test@gmail.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('requires valid email format', function () {
    $response = $this->postJson('/register', [
        'email' => 'invalid-email',
        'username' => 'testuser',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('prevents duplicate email registration', function () {
    User::factory()->create(['email' => 'existing@gmail.com']);

    $response = $this->postJson('/register', [
        'email' => 'existing@gmail.com',
        'username' => 'newuser',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('prevents duplicate username registration', function () {
    User::factory()->create(['username' => 'existinguser']);

    $response = $this->postJson('/register', [
        'email' => 'new@gmail.com',
        'username' => 'existinguser',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('sends magic link email to registered user', function () {
    $this->postJson('/register', [
        'email' => 'test@gmail.com',
        'username' => 'testuser',
    ]);

    $user = User::where('email', 'test@gmail.com')->first();

    Mail::assertQueued(\App\Mail\MagicLinkMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email) && $mail->type === 'register';
    });
});

it('defaults to english locale when not provided', function () {
    $this->postJson('/register', [
        'email' => 'test@gmail.com',
        'username' => 'testuser',
    ]);

    $user = User::where('email', 'test@gmail.com')->first();
    expect($user->locale)->toBe(Locale::ENGLISH);
});

it('uses provided locale when registering', function () {
    $this->postJson('/register', [
        'email' => 'test@gmail.com',
        'username' => 'testuser',
        'locale' => Locale::JAPANESE->value,
    ]);

    $user = User::where('email', 'test@gmail.com')->first();
    expect($user->locale)->toBe(Locale::JAPANESE);
});

it('enforces rate limiting when registering', function () {
    $ip = '127.0.0.1';

    // Register 5 times (the limit)
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->postJson('/register', [
            'email' => "test{$i}@gmail.com",
            'username' => "testuser{$i}",
        ], [
            'REMOTE_ADDR' => $ip,
        ]);

        $response->assertSuccessful();
    }

    // Try to register a 6th time (should be rate limited)
    $response = $this->postJson('/register', [
        'email' => 'test6@gmail.com',
        'username' => 'testuser6',
    ], [
        'REMOTE_ADDR' => $ip,
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'message' => 'You can only register 5 times per hour. Please try again later.',
        ]);
});

it('allows registration after rate limit expires', function () {
    $ip = '127.0.0.1';
    $rateLimitKey = "register:ip:{$ip}";

    // Register once
    $this->postJson('/register', [
        'email' => 'test1@gmail.com',
        'username' => 'testuser1',
    ], [
        'REMOTE_ADDR' => $ip,
    ])->assertSuccessful();

    // Clear rate limiter
    RateLimiter::clear($rateLimitKey);

    // Should be able to register again
    $response = $this->postJson('/register', [
        'email' => 'test2@gmail.com',
        'username' => 'testuser2',
    ], [
        'REMOTE_ADDR' => $ip,
    ]);

    $response->assertSuccessful();
});
