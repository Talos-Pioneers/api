<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        if ($provider !== 'google') {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        if ($provider !== 'google') {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            $providerRecord = Provider::where('provider', $provider)
                ->where('provider_user_id', $socialUser->getId())
                ->first();

            if ($providerRecord) {
                // User exists, log them in
                Auth::login($providerRecord->user);

                return redirect()->intended('/');
            }

            // Check if user exists with this email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // User exists but doesn't have this provider linked
                Provider::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_user_id' => $socialUser->getId(),
                ]);

                Auth::login($user);

                return redirect()->intended('/');
            }

            // New user - create account
            $user = User::create([
                'email' => $socialUser->getEmail(),
                'username' => $this->generateRandomUsername(),
                'locale' => $this->detectLocaleFromProvider($socialUser),
            ]);

            Provider::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_user_id' => $socialUser->getId(),
            ]);

            Auth::login($user);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            return redirect('/')->withErrors([
                'error' => 'Authentication failed. Please try again.',
            ]);
        }
    }

    private function generateRandomUsername(): string
    {
        do {
            $username = 'user_'.Str::random(16);
        } while (User::where('username', $username)->exists());

        return $username;
    }

    private function detectLocaleFromProvider($socialUser): Locale
    {
        $rawUser = $socialUser->user ?? [];
        $locale = $rawUser['locale'] ?? $rawUser['language'] ?? null;

        if ($locale) {
            $localeMatch = Locale::fromString($locale);

            if ($localeMatch) {
                return $localeMatch;
            }

            $languageCode = explode('-', $locale)[0];

            $languageMatch = Locale::fromString($languageCode);

            return $languageMatch ?? Locale::ENGLISH;
        }

        return Locale::ENGLISH;
    }
}
