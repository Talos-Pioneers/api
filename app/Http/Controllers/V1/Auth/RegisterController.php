<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class RegisterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(HandlePrecognitiveRequests::class, only: ['store']),
        ];
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $ip = $request->ip();
        $rateLimitKey = "register:ip:{$ip}";

        if (! RateLimiter::attempt(
            $rateLimitKey,
            5,
            function () {
                // No-op: Only for rate limiting
            },
            3600 // 1 hour in seconds
        )) {
            return response()->json([
                'message' => __('You can only register 5 times per hour. Please try again later.'),
            ], 429);
        }

        $locale = $request->validated('locale') ? Locale::fromString($request->validated('locale')) : Locale::ENGLISH;

        $user = User::create([
            'email' => $request->validated('email'),
            'username' => $request->validated('username'),
            'locale' => $locale,
        ]);

        $action = new LoginAction($user);
        $action->remember();
        $action->redirect(redirect()->away(config('app.frontend_url')));

        $magicLink = MagicLink::create(
            $action,
            4320,
            1,
        );

        Mail::to($user->email)->queue(new MagicLinkMail($magicLink->url, 'register'));

        return response()->json([
            'message' => __('Registration successful. Please check your email for the magic link.'),
        ]);
    }
}
