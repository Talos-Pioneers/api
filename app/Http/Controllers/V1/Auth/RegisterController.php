<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->validated('email'),
            'username' => $request->validated('username'),
            'locale' => $request->validated('locale') ?? Locale::ENGLISH,
        ]);

        $magicLink = MagicLink::create(
            new LoginAction($user),
            4320,
            1,
        );

        Mail::to($user->email)->send(new MagicLinkMail($magicLink->url, 'register'));

        return response()->json([
            'message' => 'Registration successful. Please check your email for the magic link.',
        ]);
    }
}
