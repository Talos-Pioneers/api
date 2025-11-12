<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class LoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $magicLink = MagicLink::create(
            new LoginAction($user)
        );

        Mail::to($user->email)->send(new MagicLinkMail($magicLink->url, 'login'));

        return response()->json([
            'message' => 'Please check your email for the magic link.',
        ]);
    }
}
