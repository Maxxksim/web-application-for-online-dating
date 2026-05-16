<?php

namespace App\Http\Controllers;

use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MatchController extends Controller
{
    public function __construct(private readonly MatchService $matchService)
    {

    }

    public function getMatches(Request $request): JsonResponse
    {
        if (($matches = $this->matchService->getMatchedProfiles($request->user()))->isEmpty()) {
            return response()->json(['message' => 'You haven\'t have matches yet.'], Response::HTTP_OK);
        }

        return response()->json(['matches' => $matches->toResourceCollection()], Response::HTTP_OK);
    }
}
