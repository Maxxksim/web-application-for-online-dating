<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = Db::transaction(function () use ($request) {
            $user = User::create($request->validated());
            $user->profile()->create();

            return $user;
        });

        return response()->json(
            ['token' => $user->createToken('auth', expiresAt: now()->addMonth())->plainTextToken],
            Response::HTTP_CREATED
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $userData = $request->validated();
        $user = User::where('email', $userData['email'])->first();

        if (!$user || !Hash::check($userData['password'], $user->password)) {
            return response()->json(['error' => 'Wrong login credentials'], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['token' => $user->createToken('auth', expiresAt: now()->addMonth())->plainTextToken]);
    }


}
