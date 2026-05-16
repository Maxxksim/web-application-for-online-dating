<?php

namespace App\Http\Controllers;

use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LikeController extends Controller
{
    public function __construct(private readonly LikeService $likeService)
    {

    }

    public function getLikes(Request $request): JsonResponse
    {
        if (($likes = $this->likeService->getProfilesWhoLiked($request->user()))->isEmpty()) {
            return response()->json(['message' => 'Nobody hasn\'t liked you yet.'], Response::HTTP_OK);
        }

        return response()->json(['likes' => $likes->toResourceCollection()], Response::HTTP_OK);
    }
}
