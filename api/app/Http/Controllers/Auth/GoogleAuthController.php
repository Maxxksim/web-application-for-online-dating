<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(): Response
    {
        $frontendUrl = config('app.frontend_url');

        try {
            $user = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return response("<script>window.opener.postMessage({error: 'auth_failed'}, '{$frontendUrl}'); window.close();</script>");
        }

        if ($existingUser = User::where('email', $user->email)->first()) {
            $token = $existingUser->createToken('auth', expiresAt: now()->addMonth())->plainTextToken;

            return response("<script>window.opener.postMessage({ token: '{$token}' }, '{$frontendUrl}');window.close();</script>");
        }

        $token = Db::transaction(function () use ($user) {
            $newUser = User::create([
                'email' => $user->email,
                'password' => bcrypt(Str::random(16)),
            ]);
            $newUser->profile()->create();
            $newUser->searchFilter()->create();

            return $newUser->createToken('auth', expiresAt: now()->addMonth())->plainTextToken;
        });

        return response("<script>window.opener.postMessage({ token: '{$token}' }, '{$frontendUrl}');window.close();</script>");
    }
}
