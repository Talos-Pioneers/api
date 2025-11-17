<?php

use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

it('redirects to google oauth', function () {
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('redirect')
        ->andReturn(redirect('https://accounts.google.com/oauth/authorize'));

    $response = $this->get('/auth/google/redirect');

    $response->assertRedirect();
});

it('returns 404 for unsupported provider', function () {
    $response = $this->get('/auth/facebook/redirect');

    $response->assertNotFound();
});

it('creates new user when authenticating with google for first time', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->email = 'newuser@example.com';
    $socialiteUser->name = 'New User';
    $socialiteUser->nickname = 'newuser';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(config('app.frontend_url'));

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->username)->toStartWith('user_');
    expect($user->username)->toHaveLength(21); // 'user_' + 16 random chars
    expect(Provider::where('provider', 'google')
        ->where('provider_user_id', '123456789')
        ->exists())->toBeTrue();
});

it('logs in existing user with google provider', function () {
    $user = User::factory()->create(['email' => 'existing@gmail.com']);
    Provider::factory()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => '123456789',
    ]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->email = 'existing@gmail.com';
    $socialiteUser->name = 'Existing User';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(config('app.frontend_url'));
    $this->assertAuthenticatedAs($user);
});

it('links google provider to existing user with same email', function () {
    $user = User::factory()->create(['email' => 'existing@gmail.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->email = 'existing@gmail.com';
    $socialiteUser->name = 'Existing User';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(config('app.frontend_url'));
    $this->assertAuthenticatedAs($user);

    expect(Provider::where('user_id', $user->id)
        ->where('provider', 'google')
        ->where('provider_user_id', '123456789')
        ->exists())->toBeTrue();
});

it('generates random username for provider users', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->email = 'another@gmail.com';
    $socialiteUser->name = 'Another User';
    $socialiteUser->nickname = 'newuser';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $this->get('/auth/google/callback');

    $user = User::where('email', 'another@gmail.com')->first();
    expect($user->username)->toStartWith('user_');
    expect($user->username)->not->toContain('newuser');
    expect($user->username)->not->toContain('Another');
});

it('detects locale from google openid data', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->email = 'japanese@gmail.com';
    $socialiteUser->user = ['locale' => 'ja-JP'];

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $this->get('/auth/google/callback');

    $user = User::where('email', 'japanese@gmail.com')->first();
    expect($user->locale->value)->toBe('ja-JP');
});

it('defaults to english locale when locale not available from provider', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '123456789';
    $socialiteUser->email = 'test@gmail.com';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $this->get('/auth/google/callback');

    $user = User::where('email', 'test@gmail.com')->first();
    expect($user->locale->value)->toBe('en-US');
});

it('returns 404 for unsupported provider callback', function () {
    $response = $this->get('/auth/facebook/callback');

    $response->assertNotFound();
});

it('redirects to discord oauth', function () {
    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('redirect')
        ->andReturn(redirect('https://discord.com/oauth2/authorize'));

    $response = $this->get('/auth/discord/redirect');

    $response->assertRedirect();
});

it('creates new user when authenticating with discord for first time', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '987654321';
    $socialiteUser->email = 'discorduser@gmail.com';
    $socialiteUser->name = 'Discord User';
    $socialiteUser->nickname = 'discorduser';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $response = $this->get('/auth/discord/callback');

    $response->assertRedirect(config('app.frontend_url'));

    $user = User::where('email', 'discorduser@gmail.com')->first();
    expect($user)->not->toBeNull();
    expect($user->username)->toStartWith('user_');
    expect($user->username)->toHaveLength(21); // 'user_' + 16 random chars
    expect(Provider::where('provider', 'discord')
        ->where('provider_user_id', '987654321')
        ->exists())->toBeTrue();
});

it('logs in existing user with discord provider', function () {
    $user = User::factory()->create(['email' => 'discordexisting@gmail.com']);
    Provider::factory()->create([
        'user_id' => $user->id,
        'provider' => 'discord',
        'provider_user_id' => '987654321',
    ]);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '987654321';
    $socialiteUser->email = 'discordexisting@gmail.com';
    $socialiteUser->name = 'Discord Existing User';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $response = $this->get('/auth/discord/callback');

    $response->assertRedirect(config('app.frontend_url'));
    $this->assertAuthenticatedAs($user);
});

it('links discord provider to existing user with same email', function () {
    $user = User::factory()->create(['email' => 'discordlink@gmail.com']);

    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '987654321';
    $socialiteUser->email = 'discordlink@gmail.com';
    $socialiteUser->name = 'Discord Link User';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $response = $this->get('/auth/discord/callback');

    $response->assertRedirect(config('app.frontend_url'));
    $this->assertAuthenticatedAs($user);

    expect(Provider::where('user_id', $user->id)
        ->where('provider', 'discord')
        ->where('provider_user_id', '987654321')
        ->exists())->toBeTrue();
});

it('generates random username for discord provider users', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '987654321';
    $socialiteUser->email = 'discordanother@gmail.com';
    $socialiteUser->name = 'Another Discord User';
    $socialiteUser->nickname = 'discorduser';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $this->get('/auth/discord/callback');

    $user = User::where('email', 'discordanother@gmail.com')->first();
    expect($user->username)->toStartWith('user_');
    expect($user->username)->not->toContain('discorduser');
    expect($user->username)->not->toContain('Another');
});

it('detects locale from discord data', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '987654321';
    $socialiteUser->email = 'discordjapanese@gmail.com';
    $socialiteUser->user = ['locale' => 'ja-JP'];

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $this->get('/auth/discord/callback');

    $user = User::where('email', 'discordjapanese@gmail.com')->first();
    expect($user->locale->value)->toBe('ja-JP');
});

it('defaults to english locale when locale not available from discord provider', function () {
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = '987654321';
    $socialiteUser->email = 'discordtest@gmail.com';
    $socialiteUser->user = [];

    Socialite::shouldReceive('driver')
        ->with('discord')
        ->andReturnSelf()
        ->shouldReceive('user')
        ->andReturn($socialiteUser);

    $this->get('/auth/discord/callback');

    $user = User::where('email', 'discordtest@gmail.com')->first();
    expect($user->locale->value)->toBe('en-US');
});
