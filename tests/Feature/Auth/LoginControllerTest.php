<?php

use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

it('can request login magic link', function () {
    $user = User::factory()->create(['email' => 'test@gmail.com']);

    $response = $this->postJson('/login', [
        'email' => 'test@gmail.com',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Please check your email for the magic link.',
        ]);

    Mail::assertSent(MagicLinkMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email) && $mail->type === 'login';
    });
});

it('requires email for login', function () {
    $response = $this->postJson('/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires valid email format for login', function () {
    $response = $this->postJson('/login', [
        'email' => 'invalid-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires existing email for login', function () {
    $response = $this->postJson('/login', [
        'email' => 'nonexistent@gmail.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('sends magic link email to user', function () {
    $user = User::factory()->create(['email' => 'test@gmail.com']);

    $this->postJson('/login', [
        'email' => 'test@gmail.com',
    ]);

    Mail::assertSent(MagicLinkMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email) && $mail->type === 'login';
    });
});

it('returns 404 for non-existent user email', function () {
    $response = $this->postJson('/login', [
        'email' => 'nonexistent@gmail.com',
    ]);

    $response->assertUnprocessable();
});
