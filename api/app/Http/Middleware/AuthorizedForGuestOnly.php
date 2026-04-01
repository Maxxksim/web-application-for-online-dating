<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthorizedForGuestOnly
{
    public function handle(Request $request, Closure $next, string $guard = 'sanctum'): JsonResponse|Response
    {
        if (auth($guard)->check()) {
            return response()->json([
                'message' => 'Already authenticated',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

}
