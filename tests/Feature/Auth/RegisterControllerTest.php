<?php

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

it('can register a new user', function () {
    $response = $this->postJson('/register', [
        'email' => 'test@example.com',
        'username' => 'testuser',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Registration successful. Please check your email for the magic link.',
        ]);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
    Mail::assertSent(\App\Mail\MagicLinkMail::class, function ($mail) {
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
        'email' => 'test@example.com',
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
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/register', [
        'email' => 'existing@example.com',
        'username' => 'newuser',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('prevents duplicate username registration', function () {
    User::factory()->create(['username' => 'existinguser']);

    $response = $this->postJson('/register', [
        'email' => 'new@example.com',
        'username' => 'existinguser',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('sends magic link email to registered user', function () {
    $this->postJson('/register', [
        'email' => 'test@example.com',
        'username' => 'testuser',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    Mail::assertSent(\App\Mail\MagicLinkMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email) && $mail->type === 'register';
    });
});

it('defaults to english locale when not provided', function () {
    $this->postJson('/register', [
        'email' => 'test@example.com',
        'username' => 'testuser',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user->locale)->toBe(Locale::ENGLISH);
});

it('uses provided locale when registering', function () {
    $this->postJson('/register', [
        'email' => 'test@example.com',
        'username' => 'testuser',
        'locale' => Locale::JAPANESE->value,
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect($user->locale)->toBe(Locale::JAPANESE);
});

it('validates locale enum value', function () {
    $response = $this->postJson('/register', [
        'email' => 'test@example.com',
        'username' => 'testuser',
        'locale' => 'invalid-locale',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['locale']);
});
