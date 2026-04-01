<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(): JsonResponse
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Google authentication failed.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($existingUser = User::where('email', $user->email)->first()) {
            return response()->json(['token' => $existingUser->createToken('auth', expiresAt: now()->addMonth())->plainTextToken]);
        }

        $newUser = User::create([
            'email' => $user->email,
            'password' => bcrypt(Str::random(16)),
        ]);

        return response()->json(['token' => $newUser->createToken('auth', expiresAt: now()->addMonth())->plainTextToken]);
    }
}
