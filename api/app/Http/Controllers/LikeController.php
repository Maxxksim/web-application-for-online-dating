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
        return response()->json(['likes' => $this->likeService->getWhoLiked($request->user())], Response::HTTP_OK);
    }
}
